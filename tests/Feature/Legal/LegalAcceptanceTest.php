<?php

namespace Tests\Feature\Legal;

use App\Jobs\SendRegistrationCodeJob;
use App\Models\LegalAcceptance;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CompletesSalonSignup;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * پذیرش قوانین در ثبت‌نام سالن و مشتری، با سند، نسخه، زمان، IP و منبع (بسته‌ی مجوز 5د).
 */
class LegalAcceptanceTest extends TestCase
{
    use CompletesSalonSignup;
    use RefreshDatabase;
    use SalonContactPayload;

    private function salonPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن الماس', 'slug' => 'almas-terms', 'subscription_type' => '3m', 'owner_name' => 'سارا محمدی',
            'owner_phone' => '09121234588', 'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload(), $overrides);
    }

    private function customerCode(): string
    {
        return Queue::pushed(SendRegistrationCodeJob::class)->last()->code;
    }

    public function test_salon_signup_requires_acceptance_and_records_salon_terms_and_privacy_with_version_and_ip(): void
    {
        $this->post(route('salon-signup.store'), $this->salonPayload(['accept_terms' => null]))->assertSessionHasErrors('accept_terms');
        $this->get(route('salon-signup.create'))->assertSee('name="accept_terms"', false);

        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.8'])->post(route('salon-signup.store'), $this->salonPayload())
            ->assertRedirect(route('salon-signup.verify'));
        $this->confirmSalonSignup();

        $salon = Salon::where('slug', 'almas-terms')->firstOrFail();
        $rows = LegalAcceptance::where('salon_id', $salon->id)->orderBy('document')->get();
        $this->assertSame(['privacy', 'salon_terms'], $rows->pluck('document')->all());
        $this->assertSame(config('legal.versions.salon_terms'), $rows->firstWhere('document', 'salon_terms')->version);
        $this->assertSame('5.6.7.8', $rows->first()->ip_address, 'IP لحظه‌ی پذیرش (فرم)، نه لحظه‌ی تأیید کد');
        $this->assertSame(User::where('phone', '09121234588')->value('id'), $rows->first()->user_id);
        $this->assertSame('web', $rows->first()->source);
    }

    public function test_customer_web_registration_records_terms_and_privacy(): void
    {
        Queue::fake();
        $form = ['name' => 'مینا', 'phone' => '09125551111', 'password' => 'password123', 'password_confirmation' => 'password123'];

        $this->post('/s/rasta/register', $form)->assertSessionHasErrors('accept_terms');
        $this->get('/s/rasta/register')->assertSee('name="accept_terms"', false);

        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.9'])->post('/s/rasta/register', $form + ['accept_terms' => '1']);
        $this->post(route('salon.register.verify'), ['code' => $this->customerCode()]);

        $user = User::where('phone', '09125551111')->sole();
        $rows = LegalAcceptance::where('user_id', $user->id)->get();
        $this->assertEqualsCanonicalizing(['terms', 'privacy'], $rows->pluck('document')->all());
        $this->assertSame(['5.6.7.9'], $rows->pluck('ip_address')->unique()->values()->all());
        $this->assertSame($user->salon_id, $rows->first()->salon_id);
    }

    public function test_the_app_sends_acceptance_and_old_apps_are_recorded_as_implicit_with_their_version(): void
    {
        Queue::fake();
        $form = ['name' => 'مینا', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $register = function (string $phone, array $extra) use ($form) {
            $start = $this->withHeaders(['X-App-Version' => '0.3.5'])
                ->postJson('/api/v1/customer/salons/rasta/register', $form + ['phone' => $phone] + $extra)->assertOk();
            $this->postJson('/api/v1/customer/salons/rasta/register/verify', [
                'challenge' => $start->json('data.challenge'), 'code' => $this->customerCode(), 'device_name' => 'A17',
            ])->assertCreated();

            return LegalAcceptance::where('user_id', User::where('phone', $phone)->value('id'))->get();
        };

        $this->assertSame(['app'], $register('09125552222', ['accept_terms' => true])->pluck('source')->unique()->values()->all());

        $implicit = $register('09125553333', []);
        $this->assertSame(['app-implicit'], $implicit->pluck('source')->unique()->values()->all());
        $this->assertSame('0.3.5', $implicit->first()->app_version);

        $this->postJson('/api/v1/customer/salons/rasta/register', $form + ['phone' => '09125554444', 'accept_terms' => false])
            ->assertStatus(422);
    }
}
