<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 1): the tenant record.
 * See "⭐⭐ فیچر برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)" in Rasta_unified_prompt.md for
 * the full architecture decisions this schema encodes — in short: `slug` is the salon's unique,
 * immutable URL (/s/{slug}); `name` is the display name the salon's own admin can rename later;
 * ownership is via the separate `salon_admins` pivot table (migration
 * 0001_01_01_000002_create_salon_admins_table), not a column here, so phase 2 ("چند ادمین روی
 * یک سالن") needs no schema migration on live data later.
 *
 * Positioned before `users` (this table's own timestamp predates 0001_01_01_000000) because
 * every salon-owned table (specialists, bookings, ...) carries a NOT NULL salon_id foreign key
 * from its own CREATE migration onward — this table has to exist first. `created_by` is
 * deliberately NOT a column here: it references `users.id`, and `users` doesn't exist yet at
 * this point, so it's added by a tiny follow-up migration
 * (0001_01_01_000001_add_created_by_to_salons_table) immediately after users is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // ⭐ تصمیم مربوط به مرچنت آیدی مجزا برای هر سالن (زرین‌پال): هر سالن می‌تواند
            // merchant_id مخصوص خودش را برای پرداخت‌های پیش‌پرداخت نوبت/شارژ کیف‌پول تنظیم کند تا
            // آن پول مستقیم به حساب زرین‌پال خودِ سالن واریز شود، نه حساب مشترک پلتفرم. عمداً
            // nullable: سالن تازه‌ساخته تا وقتی merchant_id واقعی‌اش را ثبت نکرده باید بلافاصله
            // کار کند — PaymentService در نبود این مقدار به merchant_id سراسری برمی‌گردد. این
            // ستون ربطی به پرداخت خرید/تمدید اشتراک (سالن → پلتفرم) ندارد؛ آن مسیر همیشه از
            // merchant_id سراسری استفاده می‌کند.
            $table->string('zarinpal_merchant_id')->nullable();
            // ⭐ پیگیری «محور ۳»: متن‌های بازاریابی اطراف نام سالن (مثل «با سال‌ها تجربه») قبلاً
            // generic و مشترک بین همه‌ی سالن‌ها بودن. هر دو ستون nullable — یک سالن تازه‌ساخته
            // می‌تونه اینا رو خالی بذاره، ViewComposer یک fallback عمومی معقول برمی‌گردونه.
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('max_specialists_count')->default(0); // 0 = هیچ
            $table->json('module_permissions')->nullable();
            // ⭐ فیچر «سقف/قطع پیامک ماهانه»: مقدار پیش‌فرض از config('billing.sms_quota_per_month')
            // خونده می‌شه (nullable اینجا یعنی «از پیش‌فرض پلتفرم استفاده کن») — این ستون فقط
            // برای override دستی روی یک سالن خاص است.
            $table->unsignedInteger('sms_quota_per_month')->nullable();
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
            // the application (SuperAdminService::createSalonWithAdmin(), and DatabaseSeeder for
            // the default salon) — nullable() here only avoids MySQL/MariaDB's implicit-default
            // machinery at CREATE TABLE time and never actually results in a null value in
            // practice.
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();
        });

        // ⭐ Every salon-owned table (wallet_settings, admin_wallet, loyalty_settings, ...)
        // requires a salon_id from the moment it's created — including the one baseline row each
        // of those tables seeds for itself. Since RefreshDatabase (used throughout the test
        // suite) runs migrations only, never seeders, this default salon has to be created here,
        // unconditionally, rather than in DatabaseSeeder — exactly like it always was before this
        // migration set was consolidated (see the now-removed backfill_default_salon_and_salon_id
        // migration). DatabaseSeeder's own Salon::firstOrCreate(['slug' => 'rasta'], ...) is a
        // defensive no-op against this same row for the demo-data seeding flow.
        DB::table('salons')->insert([
            'name' => 'سالن زیبایی راستا',
            'slug' => 'rasta',
            'max_specialists_count' => 100,
            'module_permissions' => null,
            'subscription_type' => '12m',
            'subscription_started_at' => now(),
            'subscription_ends_at' => now()->addMonths(12),
            'is_suspended' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('salons');
    }
};
