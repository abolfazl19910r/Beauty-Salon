<?php

namespace Tests\Feature\Console;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 7). See
 * App\Console\Commands\CreateSuperAdmin's own docblock for why the super-admin role is synced
 * directly rather than through AdminUserService's normal roles-assignment path.
 */
class CreateSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_super_admin_with_the_role_and_no_salon_attachment(): void
    {
        $this->artisan('superadmin:create', [
            'phone' => '09121110000',
            'name' => 'سوپر ادمین جدید',
            '--password' => 'password123',
        ])->assertSuccessful();

        $user = User::where('phone', '09121110000')->first();

        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertFalse($user->salons()->exists());
    }

    public function test_creates_the_super_admin_role_if_it_does_not_exist_yet(): void
    {
        $this->assertDatabaseMissing('roles', ['name' => 'super-admin']);

        $this->artisan('superadmin:create', [
            'phone' => '09121110001',
            'name' => 'سوپر ادمین دوم',
            '--password' => 'password123',
        ])->assertSuccessful();

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
    }

    public function test_rejects_a_phone_already_used_by_another_staff_member(): void
    {
        User::factory()->create(['phone' => '09121110002', 'user_type' => 'staff']);

        $this->artisan('superadmin:create', [
            'phone' => '09121110002',
            'name' => 'تکراری',
            '--password' => 'password123',
        ])->assertFailed();
    }

    public function test_rejects_a_malformed_phone(): void
    {
        $this->artisan('superadmin:create', [
            'phone' => '12345',
            'name' => 'شماره بد',
            '--password' => 'password123',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['name' => 'شماره بد']);
    }

    public function test_rejects_a_password_shorter_than_eight_characters(): void
    {
        $this->artisan('superadmin:create', [
            'phone' => '09121110003',
            'name' => 'رمز کوتاه',
            '--password' => 'abc',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['phone' => '09121110003']);
    }

    public function test_does_not_strip_the_super_admin_role_even_though_no_console_user_is_authenticated(): void
    {
        // ⭐ This is the exact scenario the command's docblock warns about: if the command had
        // gone through AdminUserService::update()/syncRoles() (or passed 'roles' into create())
        // instead of syncing the role directly, filterAssignableRoles() would have silently
        // stripped the super-admin role id here, because auth()->user() is null in a console
        // context. This test fails loudly (no role attached) if that regression is reintroduced.
        $this->assertNull(auth()->user());

        $this->artisan('superadmin:create', [
            'phone' => '09121110004',
            'name' => 'بدون کاربر لاگین‌شده',
            '--password' => 'password123',
        ])->assertSuccessful();

        $role = Role::where('name', 'super-admin')->first();
        $user = User::where('phone', '09121110004')->first();

        $this->assertNotNull($role);
        $this->assertTrue($user->roles->contains($role->id));
    }

    public function test_prompts_for_missing_arguments_and_password(): void
    {
        $this->artisan('superadmin:create')
            ->expectsQuestion('شماره موبایل سوپر ادمین (۰۹xxxxxxxxx)', '09121110005')
            ->expectsQuestion('نام سوپر ادمین', 'ساخته‌شده با پرامپت')
            ->expectsQuestion('رمز عبور (حداقل ۸ کاراکتر)', 'password123')
            ->expectsQuestion('تکرار رمز عبور', 'password123')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['phone' => '09121110005', 'name' => 'ساخته‌شده با پرامپت']);
    }

    public function test_rejects_mismatched_password_confirmation_when_prompted(): void
    {
        $this->artisan('superadmin:create')
            ->expectsQuestion('شماره موبایل سوپر ادمین (۰۹xxxxxxxxx)', '09121110006')
            ->expectsQuestion('نام سوپر ادمین', 'رمز نامتطابق')
            ->expectsQuestion('رمز عبور (حداقل ۸ کاراکتر)', 'password123')
            ->expectsQuestion('تکرار رمز عبور', 'different123')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['phone' => '09121110006']);
    }
}
