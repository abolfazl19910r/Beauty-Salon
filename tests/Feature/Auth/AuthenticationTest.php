<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
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

    /**
     * ⭐ Regression (session 7): RedirectIfAuthenticated (the 'guest' middleware guarding
     * GET /login) used to fall straight to the generic is_admin check without ever asking
     * hasRole('super-admin') first — so an already-authenticated super-admin (who, like every
     * seeded/real super-admin, also has is_admin=true) revisiting /login landed on
     * '/admin/dashboard' instead of '/superadmin/dashboard'. This must mirror
     * AuthenticatedSessionController::redirectPath(), which already gets this right.
     */
    public function test_already_authenticated_super_admin_visiting_login_is_sent_to_superadmin_panel(): void
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin->assignRole($superAdminRole);

        $response = $this->actingAs($superAdmin)->get('/login');

        $response->assertRedirect('/superadmin/dashboard');
    }

    public function test_users_with_correct_password_are_sent_to_otp_verification_not_logged_in_yet(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);

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
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);

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
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);
        $user->refresh();

        $response = $this->post('/login/verify', ['code' => $user->login_verification_code]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_verification_fails_with_the_wrong_otp_code(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password123']);

        $response = $this->post('/login/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_login_otp_code_is_single_use_and_cleared_after_successful_verification(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
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

        // ⭐ Fix: destroy() used to redirect('/') — a URL with no matching route at all in this
        // app (every '/' route lives under some prefix: /s/{slug}/ for the public homepage,
        // /admin for the admin dashboard); visiting it after logout 404'd. Now redirects to the
        // real global route('login') instead.
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
