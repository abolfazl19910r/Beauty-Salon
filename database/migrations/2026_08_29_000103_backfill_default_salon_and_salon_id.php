<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 1): makes this migration
 * safe to run against the real, already-populated production database — every existing row in
 * every salon-owned table gets attached to one default "سالن زیبایی راستا" (slug: rasta) salon,
 * and every user currently flagged is_admin=true is linked to it as 'owner' in salon_admins, so
 * nothing about current behavior changes the moment this migration runs.
 *
 * max_specialists_count for the default salon is set to whatever the CURRENT specialist count
 * already is (plus headroom) rather than a fixed guess — so existing production data can never
 * end up already over its own quota the instant this ships.
 *
 * ⚠️ MySQL fix: the previous version of this migration called
 *     $table->foreignId('salon_id')->nullable(false)->change();
 * directly on a column that already participates in the `bookings_salon_id_foreign` FK
 * (created by migration 2026_08_29_000102_add_salon_id_to_owned_tables). MySQL/MariaDB refuses
 * any ALTER TABLE … MODIFY COLUMN on a column that is part of a FK and raises
 *     SQLSTATE[HY000]: General error: 1832 Cannot change column 'salon_id':
 *     used in a foreign key constraint 'bookings_salon_id_foreign'
 * SQLite (used by the test suite) does not enforce this, so the bug only surfaces on a real
 * MySQL deploy. Fix: for every affected table, drop the FK first, MODIFY the column to NOT
 * NULL, then re-create the FK with the same ON DELETE CASCADE semantics — done inside one
 * Schema::table closure so Laravel issues the three ALTER statements sequentially against
 * the same connection. The same fix is mirrored in down() for symmetry.
 */
return new class extends Migration
{
    private const TABLES = [
        'specialists',
        'beauty_services',
        'categories',
        'blog_posts',
        'blog_categories',
        'gallery_images',
        'announcements',
        'discount_codes',
        'loyalty_settings',
        'wallet_settings',
        'admin_wallet',
        'bookings',
    ];

    public function up(): void
    {
        $currentSpecialistCount = DB::table('specialists')->count();

        $defaultSalonId = DB::table('salons')->insertGetId([
            'name' => 'سالن زیبایی راستا',
            'slug' => 'rasta',
            // بدون سقف واقعی برای سالن سیستم — دادهٔ موجود هرگز نباید همون لحظهٔ deploy، «پر» به نظر برسه.
            'max_specialists_count' => max($currentSpecialistCount + 50, 100),
            'module_permissions' => null, // null = همهٔ ماژول‌ها (معادل رفتار فعلی is_admin=true)
            'subscription_type' => '12m',
            'subscription_started_at' => now(),
            'subscription_ends_at' => now()->addMonths(12),
            'is_suspended' => false,
            'created_by' => null, // پیش از وجود سوپر ادمین ساخته شده — نه توسط کسی
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (self::TABLES as $tableName) {
            DB::table($tableName)->whereNull('salon_id')->update(['salon_id' => $defaultSalonId]);
        }

        // ⚠️ MySQL: نمی‌توان ستونی که در یک FK شرکت دارد را با MODIFY COLUMN تغییر داد
        //    (خطای 1832). ابتدا FK را دراپ می‌کنیم، ستون را NOT NULL می‌کنیم، سپس FK را
        //    با همان semantics قبلی (ON DELETE CASCADE) دوباره می‌سازیم.
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['salon_id']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('salon_id')->nullable(false)->change();
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('salon_id')
                    ->references('id')
                    ->on('salons')
                    ->cascadeOnDelete();
            });
        }

        // هر کاربر is_admin=true فعلی، مالک (owner) سالن پیش‌فرض است — بدون این محدودیت که سوپر
        // ادمین برای سالن‌های بعدی اعمال می‌کنه (حداکثر یک owner)، چون این‌ها دادهٔ قدیمی‌اند،
        // نه چیزی که SuperAdminService ساخته باشه.
        $existingAdminIds = DB::table('users')->where('is_admin', true)->pluck('id');

        $now = now();
        $salonAdminRows = $existingAdminIds->map(fn ($userId) => [
            'salon_id' => $defaultSalonId,
            'user_id' => $userId,
            'role' => 'owner',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($salonAdminRows)) {
            DB::table('salon_admins')->insert($salonAdminRows);
        }
    }

    public function down(): void
    {
        // همان الگوی up() — ابتدا FK را دراپ کن، ستون را به nullable تغییر بده، سپس FK را بازسازی کن.
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['salon_id']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('salon_id')->nullable()->change();
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('salon_id')
                    ->references('id')
                    ->on('salons')
                    ->cascadeOnDelete();
            });
        }

        DB::table('salon_admins')->delete();
        DB::table('salons')->where('slug', 'rasta')->delete();
    }
};
