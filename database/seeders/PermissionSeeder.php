<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view-users', 'label' => 'مشاهده کاربران', 'group' => 'کاربران', 'description' => 'مشاهده لیست کاربران'],
            ['name' => 'create-users', 'label' => 'ایجاد کاربر', 'group' => 'کاربران', 'description' => 'افزودن کاربر جدید'],
            ['name' => 'edit-users', 'label' => 'ویرایش کاربران', 'group' => 'کاربران', 'description' => 'ویرایش اطلاعات کاربران'],
            ['name' => 'delete-users', 'label' => 'حذف کاربران', 'group' => 'کاربران', 'description' => 'حذف کاربران از سیستم'],

            ['name' => 'view-bookings', 'label' => 'مشاهده رزروها', 'group' => 'رزروها', 'description' => 'مشاهده لیست رزروها'],
            ['name' => 'create-bookings', 'label' => 'ایجاد رزرو', 'group' => 'رزروها', 'description' => 'ثبت رزرو جدید'],
            ['name' => 'edit-bookings', 'label' => 'ویرایش رزروها', 'group' => 'رزروها', 'description' => 'ویرایش رزروهای موجود'],
            ['name' => 'delete-bookings', 'label' => 'حذف رزروها', 'group' => 'رزروها', 'description' => 'حذف رزروها'],
            ['name' => 'confirm-bookings', 'label' => 'تایید و رد رزروها', 'group' => 'رزروها', 'description' => 'تغییر وضعیت رزروها (تایید، رد)'],

            ['name' => 'view-services', 'label' => 'مشاهده خدمات', 'group' => 'خدمات', 'description' => 'مشاهده لیست خدمات و دسته‌بندی‌ها'],
            ['name' => 'manage-services', 'label' => 'مدیریت خدمات', 'group' => 'خدمات', 'description' => 'ایجاد، ویرایش و حذف خدمات و دسته‌بندی‌ها'],

            ['name' => 'view-specialists', 'label' => 'مشاهده متخصصان', 'group' => 'متخصصان', 'description' => 'مشاهده لیست متخصصان'],
            ['name' => 'manage-specialists', 'label' => 'مدیریت متخصصان', 'group' => 'متخصصان', 'description' => 'ایجاد، ویرایش، حذف متخصصان و زمان‌بندی آن‌ها'],

            ['name' => 'manage-settings', 'label' => 'مدیریت تنظیمات', 'group' => 'تنظیمات', 'description' => 'ویرایش تنظیمات سیستم'],
            ['name' => 'manage-roles', 'label' => 'مدیریت نقش‌ها', 'group' => 'تنظیمات', 'description' => 'مدیریت نقش‌ها و دسترسی‌ها'],

            ['name' => 'access_admin_panel', 'label' => 'دسترسی به پنل مدیریت', 'group' => 'تنظیمات', 'description' => 'دسترسی به پنل مدیریت سیستم'],

            // ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن».
            ['name' => 'manage-wallet', 'label' => 'مدیریت کیف‌پول و امور مالی', 'group' => 'مالی', 'description' => 'دسترسی به کیف‌پول متخصصان، تسویه، برداشت وجه، و خرید/تمدید اشتراک سالن'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userPermissions = Permission::whereIn('name', [
                'view-services',
                'create-bookings',
                'view-bookings',
            ])->get();
            $userRole->permissions()->sync($userPermissions->pluck('id'));
        }

        $specialistRole = Role::where('name', 'specialist')->first();
        if ($specialistRole) {
            $specialistPermissions = Permission::whereIn('name', [
                'access_admin_panel',
                'view-bookings',
                'edit-bookings',
                'confirm-bookings',
                'view-specialists',
            ])->get();
            $specialistRole->permissions()->sync($specialistPermissions->pluck('id'));
        }

        // ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — منشی: فقط ثبت/مدیریت نوبت دستی و
        // مشاهده‌ی موارد پایه؛ بدون مدیریت کاربران/ادمین‌ها، تنظیمات، یا مالی/کیف‌پول.
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffPermissions = Permission::whereIn('name', [
                'access_admin_panel',
                'view-bookings', 'create-bookings', 'edit-bookings', 'confirm-bookings',
                'view-services', 'view-specialists',
            ])->get();
            $staffRole->permissions()->sync($staffPermissions->pluck('id'));
        }

        // نقش افزودنی: owner می‌تونه این رو *علاوه‌بر* «منشی» فقط به یک staff خاص بده تا او به
        // کیف‌پول/مالی دسترسی داشته باشه، بدون تبدیل او به owner یا تغییر کل نقش «منشی».
        $financeRole = Role::where('name', 'finance-access')->first();
        if ($financeRole) {
            $manageWallet = Permission::where('name', 'manage-wallet')->first();
            if ($manageWallet) {
                $financeRole->permissions()->sync([$manageWallet->id]);
            }
        }
    }
}
