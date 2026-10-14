<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پلن‌ها و امکانات (بسته‌ی الف، تصمیم‌های ۲۰۲۶-۱۰-۱۴). همه سطح پلتفرم (سوپرادمین)، جز salon_feature_overrides که مال یک سالن
 * است ولی فقط سوپرادمین می‌نویسدش. salons.plan_id خالی = رفتار پیش از پلن‌ها (همه‌ی امکانات، قیمت و سهمیه از config/billing.php)؛
 * دستور plans:sync-defaults (و DatabaseSeeder) پلن‌های پیش‌فرض را می‌سازد و سالن‌های بی‌پلن را به «آزمایشی» یا «کامل» وصل می‌کند.
 * next_plan_*: خرید پلن دیگر وسط اشتراک فعال (تمدید/تنزل) از ابتدای دوره‌ی تازه اثر می‌کند، نه همان لحظه.
 */
return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('salon_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->string('feature', 40);
            $table->boolean('enabled');
            $table->string('note', 255)->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['salon_id', 'feature']);
            $table->index(['feature', 'enabled']);
        });

        Schema::table('salons', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_type')->constrained('plans')->nullOnDelete();
            $table->foreignId('next_plan_id')->nullable()->after('plan_id')->constrained('plans')->nullOnDelete();
            $table->dateTime('next_plan_starts_at')->nullable()->after('next_plan_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_type')->constrained('plans')->nullOnDelete();
            // renewal: خرید/تمدید یک بازه · upgrade: مابه‌التفاوت ارتقای وسط دوره (پایان دوره ثابت می‌ماند)
            $table->string('kind', 20)->default('renewal')->after('plan_id');
            $table->json('plan_snapshot')->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['kind', 'plan_snapshot']);
        });
        Schema::table('salons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_plan_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn('next_plan_starts_at');
        });
        Schema::dropIfExists('salon_feature_overrides');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
