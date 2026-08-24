<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature coverage for the three rate limiters added as a follow-up to throttle:auth
 * (LoginThrottlingTest) — 'registration', 'password-reset', 'phone-verification', each
 * deliberately independent (own cache bucket) from 'auth' and from each other. See the
 * comment above RouteServiceProvider::configureRateLimiting() for the full rationale: these
 * close a real SMS-spam vector (every hit sends a real Kavenegar SMS) and a real OTP
 * brute-force vector (the 6-digit codes are checked with a plain equality check and no
 * per-code attempt limit).
 *
 * Unlike 'auth' (configurable via MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES), these three are
 * intentionally hardcoded (matching the pre-existing 'sensitive' limiter's style) rather than
 * adding six more .env.example keys for values with no immediate operational need to tune.
 */
class RegistrationAndPasswordResetThrottlingTest extends TestCase
{
    use RefreshDatabase;

    // --- registration ---

    public function test_registration_is_throttled_after_five_attempts_from_the_same_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [
                'name' => 'کاربر تست',
                'phone' => '0912000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $response = $this->post('/register', [
            'name' => 'کاربر تست',
            'phone' => '09129999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['phone' => '09129999999']);
    }

    public function test_registration_resend_shares_the_registration_bucket(): void
    {
        $user = User::factory()->create();
        $this->withSession(['register_user_id' => $user->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register/resend');
        }

        $response = $this->post('/register/resend');

        $response->assertSessionHas('error');
    }

    public function test_registration_throttling_does_not_affect_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [
                'name' => 'کاربر تست',
                'phone' => '0912111'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response->assertSessionMissing('error');
        $response->assertRedirect(route('login.verify.show'));
    }

    // --- password reset ---

    public function test_password_reset_request_is_throttled_after_three_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['phone' => $user->phone]);
        }

        $response = $this->post('/forgot-password', ['phone' => $user->phone]);

        $response->assertSessionHas('error');
    }

    public function test_password_reset_throttling_prevents_sms_bombing_an_unregistered_or_registered_number_alike(): void
    {
        // The limiter is IP-scoped, not phone-scoped — it protects against an attacker hammering
        // *any* phone number(s) from one IP, not just repeated hits on a single number.
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->post('/forgot-password', ['phone' => $userA->phone]);
        $this->post('/forgot-password', ['phone' => $userB->phone]);
        $this->post('/forgot-password', ['phone' => $userA->phone]);

        $response = $this->post('/forgot-password', ['phone' => $userB->phone]);

        $response->assertSessionHas('error');
    }

    public function test_reset_password_submission_shares_the_password_reset_bucket(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $user->refresh();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        // 1 attempt already used by sendCode() above; 2 more submit() calls reach the 3-limit
        // exactly (still allowed); the 3rd submit() call is what actually exceeds it.
        $this->post('/reset-password', [
            'token' => $token, 'code' => '000000',
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);
        $this->post('/reset-password', [
            'token' => $token, 'code' => '000000',
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);

        $response = $this->post('/reset-password', [
            'token' => $token, 'code' => '000000',
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_password_reset_throttling_does_not_affect_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['phone' => $user->phone]);
        }

        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response->assertSessionMissing('error');
        $response->assertRedirect(route('login.verify.show'));
    }

    // --- phone verification ---

    public function test_phone_verification_is_throttled_after_five_attempts(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/verify-phone/verify', ['code' => '000000']);
        }

        $response = $this->actingAs($user)->postJson('/verify-phone/verify', ['code' => '000000']);

        $response->assertStatus(429);
        $response->assertJson(['success' => false]);
    }

    public function test_phone_verification_resend_shares_the_phone_verification_bucket(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post('/verify-phone/resend');
        }

        $response = $this->actingAs($user)->postJson('/verify-phone/verify', ['code' => '000000']);

        $response->assertStatus(429);
    }

    public function test_phone_verification_throttling_does_not_affect_login(): void
    {
        $verifyingUser = User::factory()->create(['phone_verified_at' => null]);
        $loginUser = User::factory()->create(['password' => bcrypt('password123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($verifyingUser)->postJson('/verify-phone/verify', ['code' => '000000']);
        }

        // A completely different, guest user hitting the unrelated /login route must not be
        // affected by the phone-verification bucket exhausted above.
        $this->post('/logout');
        $response = $this->post('/login', ['phone' => $loginUser->phone, 'password' => 'password123']);

        $response->assertSessionMissing('error');
        $response->assertRedirect(route('login.verify.show'));
    }
}
