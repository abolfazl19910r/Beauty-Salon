<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_roles_with_user_count(): void
    {
        Role::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/roles');

        $response->assertOk();
        $this->assertCount(3, $response->viewData('roles'));
    }

    public function test_store_creates_a_role_and_syncs_permissions(): void
    {
        $permissions = Permission::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->post('/admin/roles', [
            'name' => 'support-agent',
            'label' => 'کارشناس پشتیبانی',
            'permissions' => $permissions->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'support-agent', 'label' => 'کارشناس پشتیبانی']);
        $role = Role::where('name', 'support-agent')->first();
        $this->assertCount(2, $role->permissions);
    }

    public function test_store_rejects_duplicate_role_name(): void
    {
        Role::factory()->create(['name' => 'existing-role']);

        $response = $this->actingAs($this->admin)->post('/admin/roles', [
            'name' => 'existing-role',
            'label' => 'تکراری',
        ]);

        $response->assertRedirect(route('admin.roles.create'));
        $response->assertSessionHasErrors('name');
    }

    public function test_update_replaces_permission_set_entirely(): void
    {
        $role = Role::factory()->create();
        $oldPermission = Permission::factory()->create();
        $newPermission = Permission::factory()->create();
        $role->permissions()->attach($oldPermission);

        $response = $this->actingAs($this->admin)->put("/admin/roles/{$role->id}", [
            'name' => $role->name,
            'label' => 'برچسب جدید',
            'permissions' => [$newPermission->id],
        ]);

        $response->assertRedirect(route('admin.roles.show', $role));
        $role->refresh();
        $this->assertCount(1, $role->permissions);
        $this->assertSame($newPermission->id, $role->permissions->first()->id);
    }

    public function test_update_with_no_permissions_key_clears_all_permissions(): void
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::factory()->create());

        $this->actingAs($this->admin)->put("/admin/roles/{$role->id}", [
            'name' => $role->name,
            'label' => 'بدون دسترسی',
        ]);

        $role->refresh();
        $this->assertCount(0, $role->permissions);
    }

    public function test_destroy_detaches_users_and_permissions_before_deleting(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->assignRole($role);
        $role->permissions()->attach(Permission::factory()->create());

        $response = $this->actingAs($this->admin)->delete("/admin/roles/{$role->id}");

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('role_user', ['role_id' => $role->id]);
        $this->assertDatabaseMissing('permission_role', ['role_id' => $role->id]);
    }

    public function test_assign_form_excludes_users_who_already_have_the_role(): void
    {
        $role = Role::factory()->create();
        $assignedUser = User::factory()->create();
        $assignedUser->assignRole($role);
        $unassignedUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/roles/{$role->id}/assign");

        $response->assertOk();
        $userIds = $response->viewData('users')->pluck('id');
        $this->assertFalse($userIds->contains($assignedUser->id));
        $this->assertTrue($userIds->contains($unassignedUser->id));
    }

    public function test_assign_gives_role_to_a_user(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post("/admin/roles/{$role->id}/assign", [
            'user_id' => $user->id,
        ]);

        $response->assertRedirect(route('admin.roles.show', $role));
        $this->assertTrue($user->fresh()->hasRole($role->name));
    }

    public function test_assign_rejects_a_nonexistent_user(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->admin)->post("/admin/roles/{$role->id}/assign", [
            'user_id' => 999999,
        ]);

        $response->assertRedirect(route('admin.roles.assign.form', $role));
        $response->assertSessionHasErrors('user_id');
    }

    public function test_remove_user_detaches_role_from_a_specific_user(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->admin)->delete("/admin/roles/{$role->id}/users/{$user->id}");

        $response->assertRedirect(route('admin.roles.show', $role));
        $this->assertFalse($user->fresh()->hasRole($role->name));
    }

    public function test_non_admin_without_permission_cannot_access_roles(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/roles')->assertStatus(403);
    }
}
