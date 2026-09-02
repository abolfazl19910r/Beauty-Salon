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
            $table->timestamp('subscription_started_at');
            $table->timestamp('subscription_ends_at');
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
