<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_and_edit_render_for_a_logged_in_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/profile')->assertOk();
        $this->actingAs($admin)->get('/admin/profile/edit')->assertOk();
    }

    public function test_update_changes_the_admins_name(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'نام قدیمی']);

        $response = $this->actingAs($admin)->patch('/admin/profile/update', [
            'name' => 'نام جدید',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $this->assertSame('نام جدید', $admin->fresh()->name);
    }

    public function test_update_requires_a_name(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->from(route('admin.profile.edit'))->patch('/admin/profile/update', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_password_changes_the_password_when_current_password_is_correct(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => Hash::make('old-password-123')]);

        $response = $this->actingAs($admin)->put('/admin/profile/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');
        $this->assertTrue(Hash::check('new-password-456', $admin->fresh()->password));
    }

    public function test_update_password_rejects_an_incorrect_current_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => Hash::make('old-password-123')]);

        $response = $this->actingAs($admin)->from(route('admin.profile.edit'))->put('/admin/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $admin->fresh()->password));
    }

    public function test_update_password_requires_confirmation_to_match(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => Hash::make('old-password-123')]);

        $response = $this->actingAs($admin)->from(route('admin.profile.edit'))->put('/admin/profile/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'mismatched',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_a_non_admin_cannot_reach_the_admin_profile_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/profile')->assertStatus(403);
    }
}
