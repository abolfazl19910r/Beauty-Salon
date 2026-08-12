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

    // ── Registration ─────────────────────────────────────────────

    public function test_visiting_register_verify_without_a_session_redirects_to_register(): void
    {
        $response = $this->get('/register/verify');

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('error');
    }

    public function test_visiting_register_verify_with_a_deleted_user_id_in_session_redirects_and_clears_session(): void
    {
        $response = $this->withSession(['register_user_id' => 999999])->get('/register/verify');

        $response->assertRedirect(route('register'));
        $this->assertNull(session('register_user_id'));
    }

    public function test_resend_registration_code_without_a_session_fails_gracefully(): void
    {
        $response = $this->post('/register/resend');

        $response->assertSessionHasErrors('error');
    }

    public function test_resend_registration_code_with_a_deleted_user_fails_gracefully(): void
    {
        $response = $this->withSession(['register_user_id' => 999999])->post('/register/resend');

        $response->assertSessionHasErrors('error');
    }

    public function test_resend_registration_code_sends_a_new_code_for_a_valid_session(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->withSession(['register_user_id' => $user->id])->post('/register/resend');

        $response->assertSessionHas('success');
    }

    public function test_register_verify_with_wrong_code_shows_error_and_keeps_session(): void
    {
        $user = User::factory()->create([
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $response = $this->withSession(['register_user_id' => $user->id])
            ->post('/register/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_register_verify_with_expired_code_fails(): void
    {
        $user = User::factory()->create([
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->subMinute(),
        ]);

        $response = $this->withSession(['register_user_id' => $user->id])
            ->post('/register/verify', ['code' => '123456']);

        $response->assertSessionHasErrors('code');
    }

    public function test_register_verify_with_correct_code_logs_the_user_in_and_clears_session(): void
    {
        $user = User::factory()->create([
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $response = $this->withSession(['register_user_id' => $user->id])
            ->post('/register/verify', ['code' => '123456']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('register_user_id'));
    }

    // ── Login ─────────────────────────────────────────────────────

    public function test_visiting_login_verify_without_a_session_redirects_to_login(): void
    {
        $response = $this->get('/login/verify');

        $response->assertRedirect(route('login'));
    }

    public function test_login_with_wrong_password_logs_a_failed_attempt_with_the_correct_user_id(): void
    {
        $user = User::factory()->create(['phone' => '09121234567', 'password' => bcrypt('correct-password')]);

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
        $user = User::factory()->create(['phone' => '09121234567', 'password' => bcrypt('correct-password')]);

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

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
