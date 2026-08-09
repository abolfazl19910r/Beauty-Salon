<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_with_correct_password_are_sent_to_otp_verification_not_logged_in_yet(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post('/login', [
            'phone' => $user->phone,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.verify.show'));
        $this->assertGuest(); // password alone is not enough — OTP still required
        $this->assertNotNull($user->fresh()->login_verification_code);
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->from('/login')->post('/login', [
            'phone' => $user->phone,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_users_cannot_authenticate_with_an_unregistered_phone_number(): void
    {
        $response = $this->from('/login')->post('/login', [
            'phone' => '09129999999',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_user_can_complete_login_with_the_correct_otp_code(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $user->refresh();

        $response = $this->post('/login/verify', ['code' => $user->login_verification_code]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_verification_fails_with_the_wrong_otp_code(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response = $this->post('/login/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_login_otp_code_is_single_use_and_cleared_after_successful_verification(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $user->refresh();
        $code = $user->login_verification_code;

        $this->post('/login/verify', ['code' => $code]);

        $this->assertNull($user->fresh()->login_verification_code);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
