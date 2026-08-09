<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_are_redirected_to_otp_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register.verify.show'));
        $this->assertDatabaseHas('users', ['phone' => '09121234567', 'name' => 'علی محمدی']);

        // Registration alone must NOT log the user in yet — only completing OTP verification does.
        $this->assertGuest();
    }

    public function test_registration_rejects_a_phone_number_in_the_wrong_format(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '12345', // invalid format
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['phone' => '12345']);
    }

    public function test_registration_rejects_a_duplicate_phone_number(): void
    {
        User::factory()->create(['phone' => '09121234567']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_registration_generates_a_six_digit_otp_code(): void
    {
        $this->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('phone', '09121234567')->first();

        $this->assertNotNull($user->verification_code);
        $this->assertSame(6, strlen($user->verification_code));
        $this->assertNotNull($user->verification_code_expire_at);
    }

    public function test_user_can_complete_registration_with_the_correct_otp_code(): void
    {
        $this->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user = User::where('phone', '09121234567')->first();

        $response = $this->post('/register/verify', ['code' => $user->verification_code]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_registration_verification_fails_with_the_wrong_otp_code(): void
    {
        $this->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->post('/register/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_registration_verification_fails_with_an_expired_otp_code(): void
    {
        $this->post('/register', [
            'name' => 'علی محمدی',
            'phone' => '09121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $user = User::where('phone', '09121234567')->first();
        $user->update(['verification_code_expire_at' => now()->subMinute()]);

        $response = $this->post('/register/verify', ['code' => $user->verification_code]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
