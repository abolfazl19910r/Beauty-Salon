<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.show'));

        $response->assertOk();
    }

    public function test_profile_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_name_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Test User', $user->refresh()->name);
    }

    public function test_profile_update_fails_without_a_name(): void
    {
        // Regression guard: the profile form has no email field and the users table has no email
        // column at all — 'name' is the only field that should ever be required here.
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            // ⭐ Correction of an earlier, wrong assumption: '/' is NOT actually a registered
            // route in this app (every '/' lives under some prefix — /s/{slug}/ for the public
            // homepage, /admin for the admin dashboard) — the previous version of this comment
            // reasoned that redirecting there "made sense" without checking that the route
            // itself resolves at all. ProfileController::destroy() now redirects to
            // route('login') instead (same fix, same reasoning, as
            // AuthenticatedSessionController::destroy()).
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.show'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect(route('profile.show'));

        $this->assertNotNull($user->fresh());
    }
}
