<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» (feat/saas-multi-admin). پرمیشن/نقش لازم برای
 * محدودسازی دسترسی مالی «منشی» (salon_admins.role='staff', is_admin=false).
 *
 * ⚠️ نکته‌ی حیاتی: User::hasPermission() یک bypass کامل برای is_admin=true دارد (مستند در
 * EnsureSuperAdmin/PermissionMiddleware — همون anti-pattern تکراری این پروژه). یعنی محدودسازی
 * واقعی فقط وقتی معنا داره که کاربر منشی is_admin=false باشه و دسترسی‌هاش صرفاً از طریق همین
 * نقش‌های سیستمی (Role/Permission) کنترل بشه — salon_admins.role ('owner'/'staff') فقط یک برچسب
 * مالکیت سالنه، هیچ‌وقت خودش یک لایه‌ی permission نبوده و نیست.
 *
 * ⚠️ کشف حین نوشتن تست واقعی (نه فرض): permissionهای پایه‌ای که نقش «منشی» بهشون نیاز داره
 * (`access_admin_panel`, `view-bookings`, ...) فقط توسط PermissionSeeder ساخته می‌شن، نه یک
 * migration — و RefreshDatabase تست‌ها seeder صدا نمی‌زنه (دقیقاً به همین خاطر تست‌های قبلی این
 * پروژه همیشه از is_admin=true/bypass استفاده می‌کردن، نه یک permission واقعی). یعنی روی یک DB
 * تازه (چه تست، چه یک production که هنوز db:seed نشده) اون permissionهای پایه اصلاً وجود ندارن،
 * پس sync زیر روی یک لیست خالی می‌نشست و نقش «منشی» برای همیشه بدون access_admin_panel می‌موند
 * (حتی نمی‌تونست وارد /admin بشه). فیکس: این migration خودش هر permission لازم رو هم
 * firstOrCreate می‌کنه (idempotent — اگه PermissionSeeder قبلاً همون ردیف رو با unique
 * constraint روی name ساخته باشه، همون ردیف برمی‌گرده)، نه اینکه فرض کنه از قبل وجود دارن.
 *
 * به‌صورت migration (نه فقط Seeder) نوشته شده چون production از قبل داده‌ی زنده داره و اجرای
 * `db:seed` روی اون تضمین‌شده نیست — دقیقاً همون الگوی
 * `2026_08_29_000103_backfill_default_salon_and_salon_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $basePermissions = [
            ['name' => 'access_admin_panel', 'label' => 'دسترسی به پنل مدیریت', 'group' => 'تنظیمات', 'description' => 'دسترسی به پنل مدیریت سیستم'],
            ['name' => 'view-bookings', 'label' => 'مشاهده رزروها', 'group' => 'رزروها', 'description' => 'مشاهده لیست رزروها'],
            ['name' => 'create-bookings', 'label' => 'ایجاد رزرو', 'group' => 'رزروها', 'description' => 'ثبت رزرو جدید'],
            ['name' => 'edit-bookings', 'label' => 'ویرایش رزروها', 'group' => 'رزروها', 'description' => 'ویرایش رزروهای موجود'],
            ['name' => 'confirm-bookings', 'label' => 'تایید و رد رزروها', 'group' => 'رزروها', 'description' => 'تغییر وضعیت رزروها (تایید، رد)'],
            ['name' => 'view-services', 'label' => 'مشاهده خدمات', 'group' => 'خدمات', 'description' => 'مشاهده لیست خدمات و دسته‌بندی‌ها'],
            ['name' => 'view-specialists', 'label' => 'مشاهده متخصصان', 'group' => 'متخصصان', 'description' => 'مشاهده لیست متخصصان'],
        ];

        $staffPermissionIds = [];
        foreach ($basePermissions as $permissionData) {
            $permission = Permission::firstOrCreate(['name' => $permissionData['name']], $permissionData);
            $staffPermissionIds[] = $permission->id;
        }

        $manageWallet = Permission::firstOrCreate(
            ['name' => 'manage-wallet'],
            [
                'label' => 'مدیریت کیف‌پول و امور مالی',
                'group' => 'مالی',
                'description' => 'دسترسی به کیف‌پول متخصصان، تسویه، برداشت وجه، و خرید/تمدید اشتراک سالن',
            ]
        );

        // نقش «منشی» — طبق تصمیم فاز ۲ محور ۲: فقط ثبت/مدیریت نوبت دستی و مشاهده‌ی موارد پایه؛
        // بدون مدیریت کاربران/ادمین‌ها، بدون تنظیمات، بدون مالی/کیف‌پول.
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'منشی']);
        $staffRole->permissions()->syncWithoutDetaching($staffPermissionIds);

        // نقش افزودنی (composable) — owner می‌تونه این رو *علاوه‌بر* «منشی» فقط به یک staff خاص
        // بده تا او (نه لزوماً بقیه‌ی منشی‌های همون سالن) به کیف‌پول/مالی دسترسی داشته باشه، بدون
        // این‌که کل نقش «منشی» رو تغییر بده یا اون کاربر رو owner کنه.
        $financeRole = Role::firstOrCreate(['name' => 'finance-access'], ['label' => 'دسترسی مالی (افزودنی)']);
        $financeRole->permissions()->syncWithoutDetaching([$manageWallet->id]);
    }

    public function down(): void
    {
        // فقط تخصیص permissionها برگردونده می‌شه؛ خودِ ردیف‌های Role/Permission عمداً حذف نمی‌شن
        // تا اگه رفرنسی (role_user) به‌جا مونده باشه، rollback چیزی رو نشکنه.
        Role::where('name', 'staff')->first()?->permissions()->detach();
        Role::where('name', 'finance-access')->first()?->permissions()->detach();
    }
};
