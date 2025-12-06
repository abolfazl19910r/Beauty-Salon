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
            ['name' => 'delete-bookings', 'label' => 'حذف رزروها', 'group' => 'رزروها', 'description' => 'لغو و حذف رزروها'],
            ['name' => 'approve-bookings', 'label' => 'تایید رزروها', 'group' => 'رزروها', 'description' => 'تایید یا رد رزروها'],

            ['name' => 'view-services', 'label' => 'مشاهده خدمات', 'group' => 'خدمات', 'description' => 'مشاهده لیست خدمات'],
            ['name' => 'create-services', 'label' => 'ایجاد خدمات', 'group' => 'خدمات', 'description' => 'افزودن خدمات جدید'],
            ['name' => 'edit-services', 'label' => 'ویرایش خدمات', 'group' => 'خدمات', 'description' => 'ویرایش خدمات موجود'],
            ['name' => 'delete-services', 'label' => 'حذف خدمات', 'group' => 'خدمات', 'description' => 'حذف خدمات'],

            ['name' => 'view-specialists', 'label' => 'مشاهده متخصصین', 'group' => 'متخصصین', 'description' => 'مشاهده لیست متخصصین'],
            ['name' => 'create-specialists', 'label' => 'ایجاد متخصص', 'group' => 'متخصصین', 'description' => 'افزودن متخصص جدید'],
            ['name' => 'edit-specialists', 'label' => 'ویرایش متخصصین', 'group' => 'متخصصین', 'description' => 'ویرایش اطلاعات متخصصین'],
            ['name' => 'delete-specialists', 'label' => 'حذف متخصصین', 'group' => 'متخصصین', 'description' => 'حذف متخصصین'],

            ['name' => 'view-reports', 'label' => 'مشاهده گزارشات', 'group' => 'گزارشات', 'description' => 'دسترسی به گزارشات سیستم'],
            ['name' => 'export-reports', 'label' => 'خروجی گزارشات', 'group' => 'گزارشات', 'description' => 'دانلود و خروجی گرفتن از گزارشات'],

            ['name' => 'view-payments', 'label' => 'مشاهده پرداخت‌ها', 'group' => 'مالی', 'description' => 'مشاهده تراکنش‌های مالی'],
            ['name' => 'manage-payments', 'label' => 'مدیریت پرداخت‌ها', 'group' => 'مالی', 'description' => 'مدیریت کامل پرداخت‌ها'],

            ['name' => 'view-categories', 'label' => 'مشاهده دسته‌بندی‌ها', 'group' => 'دسته‌بندی‌ها', 'description' => 'مشاهده دسته‌بندی‌ها'],
            ['name' => 'manage-categories', 'label' => 'مدیریت دسته‌بندی‌ها', 'group' => 'دسته‌بندی‌ها', 'description' => 'افزودن، ویرایش و حذف دسته‌بندی‌ها'],

            ['name' => 'view-gallery', 'label' => 'مشاهده گالری', 'group' => 'گالری', 'description' => 'مشاهده تصاویر گالری'],
            ['name' => 'manage-gallery', 'label' => 'مدیریت گالری', 'group' => 'گالری', 'description' => 'آپلود و مدیریت تصاویر'],

            ['name' => 'view-blog', 'label' => 'مشاهده وبلاگ', 'group' => 'وبلاگ', 'description' => 'مشاهده مقالات وبلاگ'],
            ['name' => 'manage-blog', 'label' => 'مدیریت وبلاگ', 'group' => 'وبلاگ', 'description' => 'افزودن و ویرایش مقالات'],

            ['name' => 'view-announcements', 'label' => 'مشاهده اطلاعیه‌ها', 'group' => 'اطلاعیه‌ها', 'description' => 'مشاهده اطلاعیه‌ها'],
            ['name' => 'manage-announcements', 'label' => 'مدیریت اطلاعیه‌ها', 'group' => 'اطلاعیه‌ها', 'description' => 'ایجاد و مدیریت اطلاعیه‌ها'],

            ['name' => 'view-loyalty', 'label' => 'مشاهده امتیازات', 'group' => 'امتیازات', 'description' => 'مشاهده سیستم امتیازدهی'],
            ['name' => 'manage-loyalty', 'label' => 'مدیریت امتیازات', 'group' => 'امتیازات', 'description' => 'مدیریت امتیازات کاربران'],

            ['name' => 'view-settings', 'label' => 'مشاهده تنظیمات', 'group' => 'تنظیمات', 'description' => 'مشاهده تنظیمات سیستم'],
            ['name' => 'manage-settings', 'label' => 'مدیریت تنظیمات', 'group' => 'تنظیمات', 'description' => 'ویرایش تنظیمات سیستم'],
            ['name' => 'manage-roles', 'label' => 'مدیریت نقش‌ها', 'group' => 'تنظیمات', 'description' => 'مدیریت نقش‌ها و دسترسی‌ها'],
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
            $adminRole->permissions()->sync($allPermissions);
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userPermissions = Permission::whereIn('name', [
                'view-services',
                'create-bookings',
                'view-bookings',
            ])->get();
            $userRole->permissions()->sync($userPermissions);
        }
    }
}
