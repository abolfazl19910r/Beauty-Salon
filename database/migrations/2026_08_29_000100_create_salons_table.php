<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 1): the tenant record.
 * See "⭐⭐ فیچر برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)" in Rasta_unified_prompt.md for
 * the full architecture decisions this schema encodes — in short: `slug` is the salon's unique,
 * immutable URL (/s/{slug}); `name` is the display name the salon's own admin can rename later;
 * ownership is via the separate `salon_admins` pivot table (migration
 * 2026_08_29_000101_create_salon_admins_table), not a column here, so phase 2 ("چند ادمین روی
 * یک سالن") needs no schema migration on live data later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('max_specialists_count')->default(0); // 0 = هیچ
            $table->json('module_permissions')->nullable();
            $table->enum('subscription_type', ['1m', '3m', '6m', '12m']);
            // ⭐ Fix (confirmed against a real deploy, not just SQLite — this project's whole
            // test suite runs on SQLite, which never enforces this): on MySQL/MariaDB, a second
            // (or later) TIMESTAMP NOT NULL column with no explicit DEFAULT in the same CREATE
            // TABLE can get an implicit '0000-00-00 00:00:00' default attempted for it — the
            // first eligible TIMESTAMP column traditionally gets DEFAULT CURRENT_TIMESTAMP
            // instead, which is why subscription_started_at (declared first) was fine while
            // subscription_ends_at (declared second) failed with "SQLSTATE[42000]: ... 1067
            // Invalid default value for 'subscription_ends_at'" the moment strict/NO_ZERO_DATE
            // mode was in effect on the target server. Both columns are always set explicitly by
            // the application (SuperAdminService::createSalonWithAdmin(), and the backfill
            // migration below) — nullable() here only avoids MySQL/MariaDB's implicit-default
            // machinery at CREATE TABLE time and never actually results in a null value in
            // practice.
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('is_suspended')->default(false);
            // سالن سیستم (پیش‌فرض، id=1) هرگز نباید تعلیق/حذف بشه — چک در SuperAdminController،
            // نه اینجا؛ این ستون فقط پرچم ساده‌ی تعلیق دستی توسط سوپر ادمینه.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salons');
    }
};
