<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_code_can_be_requested_for_an_existing_phone_number(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);

        $response = $this->post('/forgot-password', ['phone' => $user->phone]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->verification_code);
        $this->assertDatabaseHas('password_reset_tokens', ['phone' => $user->phone]);
    }

    public function test_reset_code_request_fails_for_an_unregistered_phone_number(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', ['phone' => '09129999999']);

        $response->assertSessionHasErrors('phone');
    }

    public function test_reset_screen_can_be_rendered_with_a_valid_token(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $response = $this->get('/reset-password/'.$token);

        $response->assertStatus(200);
    }

    public function test_reset_screen_redirects_away_for_an_invalid_token(): void
    {
        $response = $this->get('/reset-password/not-a-real-token');

        $response->assertRedirect(route('password.request'));
    }

    public function test_password_can_be_reset_with_a_valid_code_and_token(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $user->refresh();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'code' => $user->verification_code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_the_wrong_code(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');
        $originalPassword = $user->password;

        $response = $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_password_reset_token_is_consumed_after_successful_reset(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $user->refresh();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $this->post('/reset-password', [
            'token' => $token,
            'code' => $user->verification_code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', ['phone' => $user->phone]);
    }

    /**
     * Regression guard (test-writing session 11): sendCode() used to hardcode
     * now()->addMinutes(2) regardless of the RESET_CODE_EXPIRE_MINUTES env key. It now reads
     * auth.reset_code_expire_minutes, so overriding that config at runtime should be reflected
     * in the persisted expiry timestamp.
     */
    public function test_reset_code_expiry_respects_the_configured_expire_minutes(): void
    {
        config(['auth.reset_code_expire_minutes' => 20]);
        $user = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);

        $this->post('/forgot-password', ['phone' => $user->phone]);

        $expiresAt = $user->fresh()->verification_code_expire_at;
        $this->assertNotNull($expiresAt);
        $this->assertEqualsWithDelta(
            now()->addMinutes(20)->timestamp,
            $expiresAt->timestamp,
            5
        );
    }

    /**
     * ⭐ رگرسیون (۲۰۲۶-۱۰-۱۰): /forgot-password بازیابی حساب کادر است. متخصصی که با همان شماره مشتری یک سالن هم هست
     * (ردیف مشتری قدیمی‌تر)، قبلاً کد را روی حساب مشتری می‌گرفت و رمز مشتری عوض می‌شد، نه رمز کادر.
     */
    public function test_staff_reset_changes_the_staff_account_even_if_a_customer_has_the_same_phone(): void
    {
        $customer = User::factory()->create([
            'phone' => '09121112233',
            'user_type' => 'customer',
            'password' => \Illuminate\Support\Facades\Hash::make('customer-pass-1'),
        ]);
        $staff = User::factory()->create([
            'phone' => '09121112233',
            'user_type' => 'staff',
            'salon_id' => null,
            'password' => \Illuminate\Support\Facades\Hash::make('staff-pass-1'),
        ]);

        $response = $this->post('/forgot-password', ['phone' => '09121112233']);
        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        $this->assertNull($customer->fresh()->verification_code);
        $this->assertNotNull($staff->fresh()->verification_code);

        $this->post('/reset-password', [
            'token' => $token,
            'code' => $staff->fresh()->verification_code,
            'password' => 'NewStaffPass-99',
            'password_confirmation' => 'NewStaffPass-99',
        ])->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewStaffPass-99', $staff->fresh()->password));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('customer-pass-1', $customer->fresh()->password));
    }
}
