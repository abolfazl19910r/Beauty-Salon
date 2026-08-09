<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityLogLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_attempt_is_logged_with_the_correct_user_id(): void
    {
        // Regression guard: logLogin() previously silently dropped a 3rd ($user) argument
        // (2-param signature vs 3-arg call sites), so Auth::id() — which is null pre-auth — was
        // always used, meaning failed-login security logs never attributed to the actual user.
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post('/login', ['phone' => $user->phone, 'password' => 'wrong-password']);

        $this->assertDatabaseHas('security_logs', [
            'event' => 'login_attempt',
            'user_id' => $user->id,
            'level' => 'warning',
        ]);
    }

    public function test_successful_login_after_otp_verification_logs_the_correct_user_id(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $user->refresh();

        $this->post('/login/verify', ['code' => $user->login_verification_code]);

        $this->assertDatabaseHas('security_logs', [
            'event' => 'login_attempt',
            'user_id' => $user->id,
            'level' => 'info',
        ]);
    }

    public function test_login_attempt_for_an_unregistered_phone_does_not_crash_and_logs_without_a_user_id(): void
    {
        $response = $this->post('/login', ['phone' => '09129999999', 'password' => 'whatever']);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseHas('security_logs', [
            'event' => 'login_attempt',
            'user_id' => null,
            'level' => 'warning',
        ]);
    }
}
