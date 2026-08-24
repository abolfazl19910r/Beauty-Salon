<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature coverage for wiring the already-defined 'auth' rate limiter (registered in
 * RouteServiceProvider, configurable via MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES since
 * test-writing session 11) onto real routes via throttle:auth middleware.
 *
 * Deliberately scoped to only the login flow (POST /login, /login/verify, /login/resend) —
 * see the comment above these three route definitions in routes/web/auth.php for why
 * register/reset-password/phone-verification were left out (Laravel's named rate limiter
 * shares one cache key per limiter name + ->by() value regardless of which route hit it, so
 * attaching the same limiter to unrelated flows would silently share one combined attempt
 * budget across them).
 */
class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_attempts_under_the_configured_limit_are_not_throttled(): void
    {
        config(['auth.max_login_attempts' => 5]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        for ($i = 0; $i < 4; $i++) {
            $response = $this->post('/login', [
                'phone' => $user->phone,
                'password' => 'wrong-password',
            ]);
            $response->assertStatus(302);
            $response->assertSessionHasErrors('phone'); // the normal "wrong credentials" error, not throttled
        }
    }

    public function test_the_attempt_that_exceeds_the_configured_limit_is_throttled(): void
    {
        config(['auth.max_login_attempts' => 3]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'بیش از حد مجاز',
            session('error')
        );
    }

    public function test_a_throttled_login_does_not_reveal_whether_the_password_was_actually_correct(): void
    {
        config(['auth.max_login_attempts' => 2]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        // The 3rd attempt uses the *correct* password — but the request never even reaches
        // AuthenticatedSessionController::store() because the throttle middleware runs first.
        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_a_throttled_login_returns_a_json_429_response_for_json_requests(): void
    {
        config(['auth.max_login_attempts' => 1]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        $response = $this->postJson('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        $response->assertStatus(429);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('بیش از حد مجاز', $response->json('message'));
    }

    public function test_login_verify_shares_the_same_attempt_budget_as_login(): void
    {
        config(['auth.max_login_attempts' => 3]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        // 2 failed attempts on /login, then 1 more on /login/verify — together they exhaust
        // the shared 3-attempt 'auth' bucket for this IP.
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $this->post('/login/verify', ['code' => '000000']);

        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response->assertSessionHas('error');
    }

    public function test_login_resend_counts_against_the_same_budget(): void
    {
        config(['auth.max_login_attempts' => 2]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->withSession(['login_user_id' => $user->id]);

        $this->post('/login/resend');
        $this->post('/login/resend');

        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response->assertSessionHas('error');
    }

    public function test_the_throttle_window_respects_a_configured_login_throttle_minutes(): void
    {
        config(['auth.max_login_attempts' => 1, 'auth.login_throttle_minutes' => 1]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $throttled = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $throttled->assertSessionHas('error');

        $this->travel(61)->seconds();

        $afterWindow = $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $afterWindow->assertSessionMissing('error');
        $afterWindow->assertRedirect(route('login.verify.show'));
    }

    public function test_registration_is_not_affected_by_the_login_throttle_bucket(): void
    {
        config(['auth.max_login_attempts' => 2]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        // Exhaust the login bucket for this IP...
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        // ...registration (a different, un-throttled route) must still work normally.
        $response = $this->post('/register', [
            'name' => 'کاربر جدید',
            'phone' => '09121230000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register.verify.show'));
        $response->assertSessionMissing('error');
    }

    public function test_the_guest_layout_renders_the_flashed_throttle_message(): void
    {
        config(['auth.max_login_attempts' => 1]);
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);
        $response = $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        $follow = $this->get(route('login'));
        $follow->assertSee('بیش از حد مجاز');
    }
}
