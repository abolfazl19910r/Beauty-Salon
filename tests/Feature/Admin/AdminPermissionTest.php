<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_permissions_and_distinct_groups(): void
    {
        Permission::factory()->create(['group' => 'booking']);
        Permission::factory()->create(['group' => 'booking']);
        Permission::factory()->create(['group' => 'wallet']);

        $response = $this->actingAs($this->admin)->get('/admin/permissions');

        $response->assertOk();
        $this->assertCount(3, $response->viewData('permissions'));
        $this->assertCount(2, $response->viewData('groups'));
    }

    public function test_store_creates_a_permission(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/permissions', [
            'name' => 'manage-blog',
            'label' => 'مدیریت وبلاگ',
            'group' => 'content',
            'description' => 'اجازه‌ی مدیریت مقالات وبلاگ',
        ]);

        $response->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseHas('permissions', ['name' => 'manage-blog', 'group' => 'content']);
    }

    public function test_store_rejects_duplicate_permission_name(): void
    {
        Permission::factory()->create(['name' => 'manage-blog']);

        $response = $this->actingAs($this->admin)->post('/admin/permissions', [
            'name' => 'manage-blog',
            'label' => 'تکراری',
            'group' => 'content',
        ]);

        $response->assertRedirect(route('admin.permissions.create'));
        $response->assertSessionHasErrors('name');
    }

    public function test_update_changes_the_permission_fields(): void
    {
        $permission = Permission::factory()->create(['label' => 'قدیمی']);

        $response = $this->actingAs($this->admin)->put("/admin/permissions/{$permission->id}", [
            'name' => $permission->name,
            'label' => 'جدید',
            'group' => $permission->group,
        ]);

        $response->assertRedirect(route('admin.permissions.show', $permission));
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'label' => 'جدید']);
    }

    public function test_destroy_removes_a_non_critical_permission(): void
    {
        $permission = Permission::factory()->create(['name' => 'manage-blog']);

        $response = $this->actingAs($this->admin)->delete("/admin/permissions/{$permission->id}");

        $response->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_destroy_refuses_to_delete_a_critical_permission(): void
    {
        $permission = Permission::factory()->create(['name' => 'access_admin_panel']);

        $response = $this->actingAs($this->admin)->delete("/admin/permissions/{$permission->id}");

        $response->assertRedirect(route('admin.permissions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    public function test_filter_narrows_by_group_and_search_term(): void
    {
        Permission::factory()->create(['name' => 'view-wallet', 'label' => 'مشاهده کیف پول', 'group' => 'wallet']);
        Permission::factory()->create(['name' => 'edit-wallet', 'label' => 'ویرایش کیف پول', 'group' => 'wallet']);
        Permission::factory()->create(['name' => 'manage-blog', 'label' => 'مدیریت وبلاگ', 'group' => 'content']);

        $response = $this->actingAs($this->admin)->get('/admin/permissions/filter?group=wallet');
        $response->assertOk();
        $this->assertCount(2, $response->viewData('permissions'));

        $response = $this->actingAs($this->admin)->get('/admin/permissions/filter?search=blog');
        $response->assertOk();
        $this->assertCount(1, $response->viewData('permissions'));
    }

    public function test_non_admin_without_manage_roles_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/permissions')->assertStatus(403);
    }

    public function test_user_with_manage_roles_permission_via_role_can_access(): void
    {
        $role = Role::factory()->create();
        $accessPermission = Permission::factory()->create(['name' => 'access_admin_panel']);
        $manageRolesPermission = Permission::factory()->create(['name' => 'manage-roles']);
        $role->permissions()->attach([$accessPermission->id, $manageRolesPermission->id]);

        $user = User::factory()->create(['is_admin' => false]);
        $user->assignRole($role);

        $this->actingAs($user)->get('/admin/permissions')->assertOk();
    }
}
