<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'کاربر جدید',
            'phone' => '09121234567',
            'password' => 'password123',
            'roles' => [$role->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user = User::where('phone', '09121234567')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_creating_user_without_is_active_leaves_phone_unverified(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'غیرفعال',
            'phone' => '09121234568',
            'password' => 'password123',
        ]);

        $user = User::where('phone', '09121234568')->first();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '09121234569']);

        $response = $this->actingAs($this->admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'تکراری',
                'phone' => '09121234569',
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_admin_can_update_a_user_and_sync_roles(): void
    {
        $user = User::factory()->create(['name' => 'قبل']);
        $role = Role::factory()->create();

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => 'بعد',
            'phone' => $user->phone,
            'roles' => [$role->id],
            'is_active' => '1',
        ]);

        $user->refresh();
        $this->assertSame('بعد', $user->name);
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_updating_phone_uniqueness_ignores_the_user_being_edited(): void
    {
        $user = User::factory()->create(['phone' => '09121111111']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'phone' => '09121111111', // same phone, should not trigger unique violation against itself
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
    }

    public function test_deleting_a_user_with_bookings_is_blocked(): void
    {
        $user = User::factory()->create();
        Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_a_user_without_bookings_succeeds(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_update_status_activates_and_deactivates_a_user(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}/status", ['is_active' => 1]);
        $this->assertNotNull($user->refresh()->phone_verified_at);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}/status", ['is_active' => 0]);
        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertNotSame($oldHash, $user->refresh()->password);
    }

    public function test_password_reset_requires_confirmation_to_match(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'doesnotmatch',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_can_sync_roles_independently(): void
    {
        $user = User::factory()->create();
        $roleA = Role::factory()->create();
        $roleB = Role::factory()->create();
        $user->roles()->sync([$roleA->id]);

        $this->actingAs($this->admin)->post("/admin/users/{$user->id}/roles", ['roles' => [$roleB->id]]);

        $user->refresh();
        $this->assertFalse($user->roles->contains($roleA));
        $this->assertTrue($user->roles->contains($roleB));
    }

    public function test_index_filters_by_search_role_and_status(): void
    {
        $role = Role::factory()->create();
        $matching = User::factory()->create(['name' => 'یافت‌شونده', 'phone_verified_at' => now()]);
        $matching->roles()->attach($role);
        User::factory()->create(['name' => 'دیگری', 'phone_verified_at' => null]);

        $response = $this->actingAs($this->admin)->get('/admin/users?search=یافت&role='.$role->id.'&status=active');

        $response->assertOk();
        $users = $response->viewData('users');
        $this->assertTrue($users->contains('id', $matching->id));
        $this->assertCount(1, $users);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }
}
