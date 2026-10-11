<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthResendAndEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    // ثبت‌نام: tests/Feature/Auth/RegistrationTest (فقط صفحه‌ی سالن، از ۲۰۲۶-۱۰-۱۱)

    // ── Login ─────────────────────────────────────────────────────

    public function test_visiting_login_verify_without_a_session_redirects_to_login(): void
    {
        $response = $this->get('/login/verify');

        $response->assertRedirect(route('login'));
    }

    public function test_login_with_wrong_password_logs_a_failed_attempt_with_the_correct_user_id(): void
    {
        $user = User::factory()->create(['phone' => '09121234567', 'password' => bcrypt('correct-password'), 'user_type' => 'staff']);

        $this->post('/login', ['phone' => '09121234567', 'password' => 'wrong-password']);

        $log = \App\Models\SecurityLog::where('event', 'login_attempt')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertFalse($log->context['success']);
    }

    public function test_login_with_unknown_phone_logs_a_failed_attempt_without_a_user_id(): void
    {
        $this->post('/login', ['phone' => '09129999999', 'password' => 'whatever']);

        $log = \App\Models\SecurityLog::where('event', 'login_attempt')->latest()->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertFalse($log->context['success']);
    }

    public function test_login_with_correct_credentials_dispatches_the_otp_job_and_does_not_log_in_yet(): void
    {
        Queue::fake();
        $user = User::factory()->create(['phone' => '09121234567', 'password' => bcrypt('correct-password'), 'user_type' => 'staff']);

        $response = $this->post('/login', ['phone' => '09121234567', 'password' => 'correct-password']);

        $response->assertRedirect(route('login.verify.show'));
        $this->assertGuest();
        Queue::assertPushed(SendLoginVerificationCodeJob::class);
    }

    public function test_login_resend_code_requires_an_active_session(): void
    {
        $response = $this->post('/login/resend');

        $response->assertSessionHasErrors('error');
    }

    public function test_login_verify_with_correct_code_logs_login_success_with_user_id(): void
    {
        $user = User::factory()->create([
            'login_verification_code' => '654321',
            'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $response = $this->withSession(['login_user_id' => $user->id])
            ->post('/login/verify', ['code' => '654321']);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $log = \App\Models\SecurityLog::where('event', 'login_attempt')->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->context['success']);
    }

    public function test_admin_is_redirected_to_admin_home_after_login(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'login_verification_code' => '111111',
            'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $response = $this->withSession(['login_user_id' => $admin->id])
            ->post('/login/verify', ['code' => '111111']);

        $response->assertRedirect('/admin/dashboard');
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        // ⭐ Fix: '/' isn't a registered route in this app — destroy() now redirects to
        // route('login') instead (see AuthenticatedSessionController::destroy()).
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
