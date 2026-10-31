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
 * 0001_01_01_000002_create_salon_admins_table), not a column here, so phase 2 ("چند ادمین روی
 * یک سالن") needs no schema migration on live data later.
 *
 * Positioned before `users` (this table's own timestamp predates 0001_01_01_000000) because
 * every salon-owned table (specialists, bookings, ...) carries a NOT NULL salon_id foreign key
 * from its own CREATE migration onward — this table has to exist first. `created_by` is
 * deliberately NOT a column here: it references `users.id`, and `users` doesn't exist yet at
 * this point, so 0001_01_01_000000_create_users_table adds it right after creating users (paused_by_user_id هم همین‌طور).
 *
 * ⭐ ادغام سوم migrationها (بسته‌ی 5و، ۲۰۲۶-۱۰-۳۱): `plans` و `plan_features` (قبلاً 2026_10_14_000005_create_plans_tables) پیش از
 * salons ساخته می‌شوند تا salons.plan_id/next_plan_id و invoices.plan_id از همان create کلید خارجی داشته باشند. ستون‌های بعدی salons
 * (لوگو، قالب، پیامک خوش‌آمد، توقف رزرو، پلن) هم همین‌جا، سر جای همان after() قبلی‌اند.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * پلن‌ها و امکانات (بسته‌ی الف، تصمیم‌های ۲۰۲۶-۱۰-۱۴). همه سطح پلتفرم (سوپرادمین). salons.plan_id خالی = رفتار پیش از پلن‌ها
         * (همه‌ی امکانات، قیمت و سهمیه از config/billing.php)؛ دستور plans:sync-defaults (و DatabaseSeeder) پلن‌های پیش‌فرض را می‌سازد و
         * سالن‌های بی‌پلن را به «آزمایشی» یا «کامل» وصل می‌کند. next_plan_*: خرید پلن دیگر وسط اشتراک فعال (تمدید/تنزل) از ابتدای
         * دوره‌ی تازه اثر می‌کند، نه همان لحظه.
         */
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_trial')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            // تومان؛ null = این بازه فروخته نمی‌شود
            $table->unsignedBigInteger('price_1m')->nullable();
            $table->unsignedBigInteger('price_3m')->nullable();
            $table->unsignedBigInteger('price_6m')->nullable();
            $table->unsignedBigInteger('price_12m')->nullable();
            $table->unsignedSmallInteger('included_specialists')->default(10);
            $table->unsignedBigInteger('extra_specialist_price_per_month')->default(0);
            $table->unsignedInteger('sms_quota_per_month')->default(0); // قطعه
            $table->unsignedSmallInteger('trial_days')->nullable();
            // ⭐ دسترسی قالب‌ها (۲۰۲۶-۱۰-۱۵): «چند چیدمان × چند پالت» برای هر بازه (۱ و ۳ ماهه / ۶ ماهه / ۱۲ ماهه)، جدا از طلایی ماهرو، و
            // تعداد قالب‌های پنل مدیر؛ عددهای پیش‌فرض هر پلن در App\Services\Plans\DefaultPlans.
            $table->unsignedTinyInteger('theme_admin_count')->default(0);
            $table->unsignedTinyInteger('theme_layouts')->default(0);
            $table->unsignedTinyInteger('theme_palettes')->default(0);
            $table->unsignedTinyInteger('theme_layouts_6m')->default(0);
            $table->unsignedTinyInteger('theme_palettes_6m')->default(0);
            $table->unsignedTinyInteger('theme_layouts_12m')->default(0);
            $table->unsignedTinyInteger('theme_palettes_12m')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature', 40);
            $table->timestamps();

            $table->unique(['plan_id', 'feature']);
            $table->index('feature');
        });

        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // «نام کوتاه پیامکی»: خالی = نام اصلی (نام بلند پیامک را از یک قطعه رد می‌کرد).
            $table->string('sms_name', 20)->nullable();
            $table->string('slug')->unique();
            // ⭐ پیگیری «محور ۳»: متن‌های بازاریابی اطراف نام سالن (مثل «با سال‌ها تجربه») قبلاً
            // generic و مشترک بین همه‌ی سالن‌ها بودن. هر دو ستون nullable — یک سالن تازه‌ساخته
            // می‌تونه اینا رو خالی بذاره، ViewComposer یک fallback عمومی معقول برمی‌گردونه.
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->string('logo_path')->nullable();
            // ⭐ بسته‌ی د — خوانایی لوگو (۲۰۲۶-۱۰-۱۵): لحن روشنایی لوگو (light/dark/mid، با GD هنگام آپلود) و لوگوی دوم اختیاری برای
            // پس‌زمینه‌ی تیره. لوگوهای موجود را یک بار `php artisan salons:analyze-logos` می‌سنجد.
            $table->string('logo_tone', 8)->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('logo_dark_tone', 8)->nullable();
            // قالب انتخابی سالن (۲۰۲۶-۱۰-۱۵): site_theme «چیدمان:پالت» یا mahru، admin_theme side/rail/top/dense؛ null = پیش‌فرض
            $table->string('site_theme', 40)->nullable();
            $table->string('admin_theme', 20)->nullable();
            // اطلاعات تماس و معرفی سالن (صفحه‌ی عمومی سالن)
            $table->string('address', 500)->nullable();
            $table->string('phone', 20)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->json('working_hours')->nullable();
            $table->unsignedInteger('max_specialists_count')->default(0); // 0 = هیچ
            $table->json('module_permissions')->nullable();
            // ⭐ فیچر «سقف/قطع پیامک ماهانه»: مقدار پیش‌فرض از config('billing.sms_quota_per_month')
            // خونده می‌شه (nullable اینجا یعنی «از پیش‌فرض پلتفرم استفاده کن») — این ستون فقط
            // برای override دستی روی یک سالن خاص است.
            $table->unsignedInteger('sms_quota_per_month')->nullable();
            // اعتبار پیامک خریده‌شده یا اعطاشده (قطعه)؛ منقضی نمی‌شود و بعد از سهمیه‌ی ماه مصرف می‌شود.
            $table->unsignedInteger('sms_credit')->default(0);
            $table->enum('subscription_type', ['1m', '3m', '6m', '12m']);
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('next_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->dateTime('next_plan_starts_at')->nullable();
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
            // پایان دوره‌ی آزمایشی رایگان سالنی که از ثبت‌نام عمومی ساخته شده (config('billing.trial_days')).
            $table->timestamp('trial_ends_at')->nullable();
            // بسته‌ی «ح»، ۳ (۲۰۲۶-۱۰-۱۹): ثبت‌نام با «خرید» پیامک خوش‌آمد را تا نتیجه‌ی پرداخت نگه می‌دارد
            // (App\Services\SalonSignup\SalonWelcome). پر = هنوز نرفته؛ اولین مسیری که خالی‌اش کند پیامک را می‌فرستد.
            $table->timestamp('welcome_sms_pending_at')->nullable();
            $table->boolean('is_suspended')->default(false);
            // بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو — جدا از is_suspended. paused_at پر = رزرو تازه بسته؛ paused_until اختیاری
            // (بازگشایی خودکار)، pause_reason به مشتری نشان داده می‌شود، paused_by: owner یا platform (ابرمدیر؛ مدیر نمی‌تواند بازش کند).
            // paused_by_user_id (FK به users) در create_users_table، بعد از paused_by.
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('paused_until')->nullable();
            $table->string('pause_reason', 200)->nullable();
            $table->string('paused_by', 10)->nullable();
            // ⭐ احراز سالن (بسته‌ی مجوز 5د؛ دستورالعمل سکوهای اینترنتی بند ۲ و ۴): unverified | pending | approved | rejected.
            // تا approved، صفحه‌ی عمومی و رزرو بسته (ResolveSalonFromRoute)؛ پنل مدیر باز. جزئیات هر ارسال: salon_verifications
            $table->string('verification_status', 16)->default('unverified')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salons');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
