<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Jobs\SendRegistrationCodeJob;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

/**
 * پیش از ورود در اپ «ماهرو»: کارت سالن، ثبت‌نام (حساب فقط بعد از کد) و بازیابی رمز — همه داخل یک سالن.
 */
class CustomerAuthApiTest extends CustomerApiTestCase
{
    private array $form = ['name' => 'مینا', 'phone' => '09125550000', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function lastCode(string $job): string
    {
        return Queue::pushed($job)->last()->code;
    }

    public function test_the_salon_card_is_public_and_only_shows_name_and_logo(): void
    {
        $this->getJson('/api/v1/customer/salons/rasta')
            ->assertOk()
            ->assertJsonPath('data.slug', 'rasta')
            ->assertJsonPath('data.name', $this->salon->name)
            ->assertJsonMissingPath('data.address')
            ->assertJsonMissingPath('data.rules');
    }

    public function test_an_unknown_or_suspended_salon_is_404(): void
    {
        $suspended = Salon::factory()->suspended()->create(['slug' => 'khamoosh']);

        $this->getJson('/api/v1/customer/salons/nadarim')->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->getJson('/api/v1/customer/salons/khamoosh')->assertNotFound();
        $this->postJson('/api/v1/customer/salons/khamoosh/register', $this->form)->assertNotFound();
        $this->assertNotNull($suspended);
    }

    public function test_everything_but_the_card_needs_a_customer_token(): void
    {
        $this->getJson('/api/v1/customer/services')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->getJson('/api/v1/customer/salon')->assertUnauthorized();

        $staff = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->api($staff->createToken('x', ['staff'])->plainTextToken)->getJson('/api/v1/customer/services')
            ->assertForbidden()->assertJsonPath('error.code', 'wrong_app');
    }

    public function test_registration_creates_the_account_only_after_the_code_and_returns_a_token(): void
    {
        $start = $this->postJson('/api/v1/customer/salons/rasta/register', $this->form)
            ->assertOk()
            ->assertJsonPath('data.phone_hint', '0912***0000')
            ->assertJsonStructure(['data' => ['challenge', 'expires_in', 'code_expires_in', 'resend_after']]);
        $this->assertDatabaseMissing('users', ['phone' => '09125550000']);

        $this->postJson('/api/v1/customer/salons/rasta/register/verify', [
            'challenge' => $start->json('data.challenge'), 'code' => '000000', 'device_name' => 'RN8',
        ])->assertStatus(422)->assertJsonPath('error.code', 'invalid_code')->assertJsonPath('meta.attempts_left', 4);

        $done = $this->postJson('/api/v1/customer/salons/rasta/register/verify', [
            'challenge' => $start->json('data.challenge'), 'code' => $this->lastCode(SendRegistrationCodeJob::class), 'device_name' => 'RN8',
        ])->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.account.user.name', 'مینا')
            ->assertJsonPath('data.account.salon.slug', 'rasta');

        $user = User::where('phone', '09125550000')->sole();
        $this->assertSame('customer', $user->user_type);
        $this->assertSame($this->salon->id, $user->salon_id);
        $this->assertNotNull($user->phone_verified_at);

        $this->api($done->json('data.token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.user.id', $user->id);
    }

    public function test_a_registered_phone_is_refused_before_any_sms(): void
    {
        $this->postJson('/api/v1/customer/salons/rasta/register', ['phone' => '09121110000'] + $this->form)
            ->assertStatus(422)->assertJsonPath('error.code', 'phone_taken');

        Queue::assertNotPushed(SendRegistrationCodeJob::class);
    }

    public function test_a_registration_challenge_only_works_in_its_own_salon(): void
    {
        Salon::factory()->create(['slug' => 'golbahar']);
        $challenge = $this->postJson('/api/v1/customer/salons/rasta/register', $this->form)->json('data.challenge');

        $this->postJson('/api/v1/customer/salons/golbahar/register/verify', [
            'challenge' => $challenge, 'code' => $this->lastCode(SendRegistrationCodeJob::class), 'device_name' => 'RN8',
        ])->assertStatus(410)->assertJsonPath('error.code', 'challenge_expired');

        $this->assertDatabaseMissing('users', ['phone' => '09125550000']);
    }

    public function test_resend_respects_the_cooldown(): void
    {
        $challenge = $this->postJson('/api/v1/customer/salons/rasta/register', $this->form)->json('data.challenge');

        $this->postJson('/api/v1/customer/salons/rasta/register/resend', ['challenge' => $challenge])
            ->assertStatus(429)->assertJsonPath('error.code', 'resend_too_soon');
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/customer/salons/rasta/register/resend', ['challenge' => $challenge])->assertOk();

        Queue::assertPushed(SendRegistrationCodeJob::class, 2);
    }

    public function test_password_reset_changes_the_password_and_signs_every_device_out(): void
    {
        $start = $this->postJson('/api/v1/customer/salons/rasta/password/forgot', ['phone' => '09121110000'])->assertOk();

        $this->postJson('/api/v1/customer/salons/rasta/password/reset', [
            'challenge' => $start->json('data.challenge'),
            'code' => $this->customer->fresh()->verification_code,
            'password' => 'new-password-9', 'password_confirmation' => 'new-password-9',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-9', $this->customer->fresh()->password));
        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_password_reset_is_per_salon(): void
    {
        $other = Salon::factory()->create(['slug' => 'golbahar']);
        $otherCustomer = $this->customerOf($other, '09129990000');

        $this->postJson('/api/v1/customer/salons/rasta/password/forgot', ['phone' => '09129990000'])
            ->assertStatus(422)->assertJsonPath('error.code', 'account_not_found');

        $challenge = $this->postJson('/api/v1/customer/salons/golbahar/password/forgot', ['phone' => '09129990000'])->json('data.challenge');
        $this->postJson('/api/v1/customer/salons/rasta/password/reset', [
            'challenge' => $challenge, 'code' => $otherCustomer->fresh()->verification_code,
            'password' => 'new-password-9', 'password_confirmation' => 'new-password-9',
        ])->assertStatus(410);
    }
}
