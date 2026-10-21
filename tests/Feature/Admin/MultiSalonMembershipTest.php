<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\AdminSalonSelector;
use App\Support\CurrentSalon;
use App\Support\RoleAssignments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ⭐ مالک/منشی چندسالنی (تصمیم ۵، ۲۰۲۶-۱۰-۲۲؛ ۵-۱ الف، ۵-۲ الف، ۵-۳ الف): یک حساب کادر در چند سالن با نقش جدای هر سالن،
 * انتخاب سالن بعد از ورود و جابه‌جایی از هدر، نقش‌ها و is_admin فقط در سالن خودشان، و محدودیت‌های حساب مشترک.
 */
class MultiSalonMembershipTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\SalonContactPayload;

    private Salon $salonA;

    private Salon $salonB;

    private User $ownerA;

    private User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->ownerA = User::factory()->create(['is_admin' => true, 'phone' => '09120000001', 'user_type' => 'staff']);
        $this->salonB = Salon::factory()->create(['slug' => 'golbahar-multi']);
        $this->ownerB = User::factory()->create(['is_admin' => true, 'phone' => '09120000002', 'user_type' => 'staff']);
        DB::table('salon_admins')->where('user_id', $this->ownerB->id)->delete();
        $this->salonB->admins()->attach($this->ownerB->id, ['role' => 'owner']);
    }

    private function inSalonB(): static
    {
        app(CurrentSalon::class)->set($this->salonB);

        return $this->withSession([AdminSalonSelector::SESSION_KEY => $this->salonB->id]);
    }

    public function test_an_owner_adds_an_existing_account_of_another_salon_as_staff(): void
    {
        $this->inSalonB()->actingAs($this->ownerB)->post('/admin/users', [
            'phone' => '09120000001', 'salon_role' => 'staff',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('success', fn ($m) => str_contains($m, 'حساب موجود'));

        $this->assertSame(1, User::where('phone', '09120000001')->count(), 'حساب تازه ساخته نشد');
        $this->assertSame('staff', $this->ownerA->fresh()->salonRoleIn($this->salonB->id));
        $this->assertSame('owner', $this->ownerA->fresh()->salonRoleIn($this->salonA->id));
        $staffRole = Role::withoutGlobalScopes()->where('name', 'staff')->value('id');
        $this->assertDatabaseHas('role_user', ['user_id' => $this->ownerA->id, 'role_id' => $staffRole, 'salon_id' => $this->salonB->id]);

        // دوباره: همین حالا عضو است
        $this->post('/admin/users', ['phone' => '09120000001', 'salon_role' => 'staff'])->assertSessionHasErrors('phone');
    }

    public function test_is_admin_and_roles_only_count_in_their_own_salon(): void
    {
        app(\App\Services\Salon\SalonMembershipService::class)->addExisting($this->ownerA, $this->salonB, 'staff',
            Role::withoutGlobalScopes()->where('name', 'staff')->pluck('id')->all());
        $finance = Role::withoutGlobalScopes()->where('name', 'finance-access')->value('id');
        RoleAssignments::give($this->ownerA, [$finance], $this->salonA->id); // مالی فقط در A
        $user = $this->ownerA->fresh();

        app(CurrentSalon::class)->set($this->salonA);
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->hasPermission('manage-wallet'));

        app(CurrentSalon::class)->set($this->salonB);
        $user = $this->ownerA->fresh();
        $this->assertFalse($user->is_admin, 'در سالنِ منشی‌گری دور زدن کامل مجوزها نیست');
        $this->assertFalse($user->hasRole('finance-access'), 'نقش مالیِ سالن A در B کار نمی‌کند');
        $this->assertFalse($user->hasPermission('manage-wallet'));
        $this->assertTrue($user->hasPermission('access_admin_panel'));

        app(CurrentSalon::class)->clear();
        $this->assertTrue($this->ownerA->fresh()->is_admin, 'بیرون از سالن ستون خام');
    }

    public function test_after_login_a_multi_salon_member_chooses_a_salon_and_switches_from_the_header(): void
    {
        app(\App\Services\Salon\SalonMembershipService::class)->addExisting($this->ownerA, $this->salonB, 'staff',
            Role::withoutGlobalScopes()->where('name', 'staff')->pluck('id')->all());
        app(CurrentSalon::class)->clear();

        $this->actingAs($this->ownerA)->get('/admin')->assertRedirect(route('admin.salons.choose'));
        $this->get(route('admin.salons.choose'))->assertOk()
            ->assertSee('data-admin-salon="'.$this->salonA->id.'"', false)
            ->assertSee('data-admin-salon="'.$this->salonB->id.'"', false)
            ->assertSee('منشی');

        $this->post(route('admin.salons.switch'), ['salon_id' => $this->salonB->id])->assertRedirect(route('admin.home'));
        $this->assertSame($this->salonB->id, session(AdminSalonSelector::SESSION_KEY));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('data-salon-switcher', false);
        // منشی بدون دسترسی مالی در B
        $this->get(route('admin.wallet.index'))->assertForbidden();

        $this->post(route('admin.salons.switch'), ['salon_id' => $this->salonA->id]);
        app(CurrentSalon::class)->clear();
        $this->get(route('admin.wallet.index'))->assertOk();

        // سالنی که عضوش نیست، با دستکاری فرم یا نشست
        $other = Salon::factory()->create(['slug' => 'not-mine']);
        $this->post(route('admin.salons.switch'), ['salon_id' => $other->id])->assertRedirect(route('admin.salons.choose'))->assertSessionHasErrors('salon');
        app(CurrentSalon::class)->clear();
        $this->withSession([AdminSalonSelector::SESSION_KEY => $other->id])->get('/admin')->assertRedirect(route('admin.salons.choose'));
    }

    public function test_a_suspended_salon_is_skipped_but_the_others_stay_open(): void
    {
        app(\App\Services\Salon\SalonMembershipService::class)->addExisting($this->ownerA, $this->salonB, 'owner');
        $this->salonB->forceFill(['is_suspended' => true])->save();
        app(CurrentSalon::class)->clear();

        $this->actingAs($this->ownerA)->withSession([AdminSalonSelector::SESSION_KEY => $this->salonB->id])
            ->get('/admin')->assertRedirect(route('admin.salons.choose'));
        $this->assertAuthenticatedAs($this->ownerA);
        $this->post(route('admin.salons.switch'), ['salon_id' => $this->salonB->id])->assertSessionHasErrors('salon');
    }

    public function test_a_shared_account_is_not_renamed_or_reset_from_one_salon_and_delete_only_leaves(): void
    {
        app(\App\Services\Salon\SalonMembershipService::class)->addExisting($this->ownerA, $this->salonB, 'staff',
            Role::withoutGlobalScopes()->where('name', 'staff')->pluck('id')->all());

        $this->inSalonB()->actingAs($this->ownerB)->get(route('admin.users.edit', $this->ownerA))->assertOk()->assertSee('data-shared-account', false);

        $this->put(route('admin.users.update', $this->ownerA), [
            'name' => 'نام تازه', 'phone' => '09120000001', 'salon_role' => 'staff', 'is_active' => '1',
        ])->assertSessionHasErrors('name');
        $this->assertNotSame('نام تازه', $this->ownerA->fresh()->name);

        $this->put(route('admin.users.password.reset', $this->ownerA), ['password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertSessionHas('error');

        $this->delete(route('admin.users.destroy', $this->ownerA))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $this->ownerA->id]);
        $this->assertNull($this->ownerA->fresh()->salonRoleIn($this->salonB->id));
        $this->assertSame('owner', $this->ownerA->fresh()->salonRoleIn($this->salonA->id));
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->ownerA->id, 'salon_id' => $this->salonB->id]);
    }

    public function test_joining_a_second_salon_pins_old_global_role_rows_to_the_first_salon(): void
    {
        $finance = Role::withoutGlobalScopes()->where('name', 'finance-access')->value('id');
        DB::table('role_user')->insert(['user_id' => $this->ownerA->id, 'role_id' => $finance, 'salon_id' => null]);

        app(\App\Services\Salon\SalonMembershipService::class)->addExisting($this->ownerA, $this->salonB, 'staff');

        $this->assertDatabaseHas('role_user', ['user_id' => $this->ownerA->id, 'role_id' => $finance, 'salon_id' => $this->salonA->id]);
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->ownerA->id, 'role_id' => $finance, 'salon_id' => null]);
    }

    public function test_super_admin_adds_an_existing_account_to_a_salon(): void
    {
        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();
        app(CurrentSalon::class)->clear();

        $this->actingAs($super)->post(route('superadmin.salons.members.store', $this->salonB), ['member_phone' => '۰۹۱۲۰۰۰۰۰۰۱', 'member_role' => 'owner'])
            ->assertSessionHas('success');
        $this->assertSame('owner', $this->ownerA->fresh()->salonRoleIn($this->salonB->id));
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'salon.member_added', 'salon_id' => $this->salonB->id]);

        $this->post(route('superadmin.salons.members.store', $this->salonB), ['member_phone' => '09129999999', 'member_role' => 'staff'])
            ->assertSessionHasErrors('member_phone');
        $this->get(route('superadmin.salons.edit', $this->salonB))->assertOk()->assertSee('data-owner-shared', false);
    }

    public function test_a_signed_in_owner_creates_a_second_salon_without_a_trial(): void
    {
        app(CurrentSalon::class)->clear();

        $this->actingAs($this->ownerA)->get(route('salon-signup.create'))->assertOk()
            ->assertSee('data-existing-owner', false)->assertDontSee('id="owner_password"', false);

        $response = $this->post(route('salon-signup.store'), [
            'name' => 'سالن دوم', 'slug' => 'second-salon', 'specialists_count' => 3,
        ] + $this->salonContactPayload());

        $salon = Salon::where('slug', 'second-salon')->firstOrFail();
        $response->assertRedirect(route('admin.billing.index'));
        $this->assertSame('owner', $this->ownerA->fresh()->salonRoleIn($salon->id));
        $this->assertNull($salon->trial_ends_at, 'دوره‌ی رایگان فقط اولین سالن');
        $this->assertTrue($salon->subscription_ends_at->isPast());
        $this->assertSame($salon->id, session(AdminSalonSelector::SESSION_KEY));
        $this->assertSame(1, User::where('phone', '09120000001')->count());
    }

    public function test_signup_with_an_existing_staff_phone_says_sign_in_first(): void
    {
        app(CurrentSalon::class)->clear();

        $this->post(route('salon-signup.store'), [
            'name' => 'سالن دوم', 'slug' => 'second-salon', 'specialists_count' => 3,
            'owner_name' => 'x', 'owner_phone' => '09120000001', 'owner_password' => 'Password-123', 'owner_password_confirmation' => 'Password-123',
        ] + $this->salonContactPayload())->assertSessionHasErrors(['owner_phone' => 'این شماره حساب ماهرو دارد (مدیر یا منشی سالن دیگر). برای ساختن سالن دوم، اول با همین شماره وارد شوید و دوباره این صفحه را باز کنید.']);
    }
}
