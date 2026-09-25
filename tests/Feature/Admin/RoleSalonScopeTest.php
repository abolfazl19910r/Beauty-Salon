<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۲۰۲۶-۰۹-۲۷: نقش‌ها و مجوزها مختص هر سالن. جدول roles برای کل پلتفرم یکی بود: مدیر هر سالن نقش‌های
 * سالن‌های دیگه رو می‌دید و تغییر می‌داد، نقش‌های سیستمی (admin، specialist و …) رو که کد با نامشون چک می‌کنه برای
 * همه‌ی سالن‌ها ویرایش/حذف می‌کرد، تعداد و فهرست کاربرهای هر نقش در کل پلتفرم رو می‌دید، و به هر کاربری در هر
 * سالنی نقش می‌داد. کاتالوگ مجوزها (نام‌هایی که کد چک می‌کنه) هم برای همه قابل ساخت/ویرایش/حذف بود.
 */
class RoleSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salonA;

    private User $owner;

    private User $ownCustomer;

    private User $otherCustomer;

    private Role $system;

    private Role $own;

    private Role $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->ownCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salonA->id, 'name' => 'ZZOWNCUSTOMER']);
        $this->system = Role::create(['name' => 'zz-system-role', 'label' => 'ZZSYSTEMROLE', 'salon_id' => null]);
        $this->own = Role::factory()->create(['name' => 'zz-own-role', 'label' => 'ZZOWNROLE']);
        $this->ownCustomer->roles()->attach($this->system);

        $salonB = Salon::factory()->create(['slug' => 'other-roles']);
        app(CurrentSalon::class)->set($salonB);
        $this->otherCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonB->id, 'name' => 'ZZOTHERCUSTOMER']);
        $this->other = Role::factory()->create(['name' => 'zz-other-role', 'label' => 'ZZOTHERROLE']);
        $this->otherCustomer->roles()->attach($this->system);
        app(CurrentSalon::class)->set($this->salonA);
    }

    public function test_role_list_shows_system_roles_and_this_salons_roles_with_salon_only_user_counts(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.roles.index'))->assertOk();

        $response->assertSee('ZZSYSTEMROLE')->assertSee('ZZOWNROLE')->assertDontSee('ZZOTHERROLE');
        $this->assertSame(1, $response->viewData('roles')->firstWhere('id', $this->system->id)->users_count);
    }

    public function test_a_system_roles_user_list_shows_only_this_salons_users(): void
    {
        $this->actingAs($this->owner)->get(route('admin.roles.show', $this->system))
            ->assertOk()->assertSee('ZZOWNCUSTOMER')->assertDontSee('ZZOTHERCUSTOMER');
    }

    public function test_another_salons_role_is_not_reachable(): void
    {
        foreach (['show', 'edit', 'assign.form'] as $route) {
            $this->actingAs($this->owner)->get(route("admin.roles.{$route}", $this->other))->assertNotFound();
        }
        $this->actingAs($this->owner)->put(route('admin.roles.update', $this->other), ['name' => 'hacked', 'label' => 'x'])->assertNotFound();
        $this->actingAs($this->owner)->delete(route('admin.roles.destroy', $this->other))->assertNotFound();

        $this->assertSame('zz-other-role', Role::withoutGlobalScopes()->find($this->other->id)->name);
    }

    public function test_a_salon_cannot_change_or_delete_a_system_role(): void
    {
        $this->actingAs($this->owner)->get(route('admin.roles.edit', $this->system))->assertForbidden();
        $this->actingAs($this->owner)->put(route('admin.roles.update', $this->system), ['name' => 'renamed', 'label' => 'x'])->assertForbidden();
        $this->actingAs($this->owner)->delete(route('admin.roles.destroy', $this->system))->assertForbidden();

        $this->assertSame('zz-system-role', Role::withoutGlobalScopes()->find($this->system->id)->name);
    }

    public function test_a_new_role_belongs_to_the_salon_and_cannot_take_a_system_roles_name(): void
    {
        $this->actingAs($this->owner)->post(route('admin.roles.store'), ['name' => 'zz-system-role', 'label' => 'x'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->owner)->post(route('admin.roles.store'), ['name' => 'zz-manager', 'label' => 'x'])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->salonA->id, Role::where('name', 'zz-manager')->value('salon_id'));
    }

    public function test_the_same_role_name_can_exist_in_two_salons(): void
    {
        $this->actingAs($this->owner)->post(route('admin.roles.store'), ['name' => 'zz-other-role', 'label' => 'x'])
            ->assertSessionHasNoErrors();
    }

    public function test_roles_are_assigned_to_and_removed_from_this_salons_users_only(): void
    {
        $form = $this->actingAs($this->owner)->get(route('admin.roles.assign.form', $this->own))->assertOk();
        $this->assertNotContains($this->otherCustomer->id, $form->viewData('users')->pluck('id'));

        $this->actingAs($this->owner)->post(route('admin.roles.assign', $this->own), ['user_id' => $this->otherCustomer->id])
            ->assertSessionHasErrors('user_id');
        $this->assertFalse($this->otherCustomer->roles()->withoutGlobalScopes()->whereKey($this->own->id)->exists());

        $this->actingAs($this->owner)->delete(route('admin.roles.remove.user', [$this->system, $this->otherCustomer]))->assertNotFound();
        $this->assertTrue($this->otherCustomer->roles()->withoutGlobalScopes()->whereKey($this->system->id)->exists());
    }

    public function test_the_permission_catalogue_is_read_only_for_a_salon(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'zz-perm'], ['label' => 'x']);

        $this->actingAs($this->owner)->get(route('admin.permissions.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.permissions.create'))->assertForbidden();
        $this->actingAs($this->owner)->post(route('admin.permissions.store'), ['name' => 'zz-new', 'label' => 'x'])->assertForbidden();
        $this->actingAs($this->owner)->put(route('admin.permissions.update', $permission), ['name' => 'zz-renamed', 'label' => 'x'])->assertForbidden();
        $this->actingAs($this->owner)->delete(route('admin.permissions.destroy', $permission))->assertForbidden();

        $this->assertSame('zz-perm', $permission->fresh()->name);
    }
}
