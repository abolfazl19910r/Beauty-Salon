<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فیچر «دوره‌ی آزمایشی رایگان» (۲۰۲۶-۰۹-۲۳). فقط یک ستون nullable — نه یک وضعیت/enum جدید —
 * چون خودِ دسترسی آزمایشی کاملاً با همون subscription_ends_at موجود کار می‌کنه (سالن آزمایشی
 * دقیقاً یک سالن «فعال» با تاریخ پایان کوتاهه). این ستون فقط «این سالن آزمایشی رو گرفته و کِی
 * تموم می‌شه» رو ثبت می‌کنه، برای نمایش در پنل و برای برگردوندن سهمیه‌ی پیامک آزمایشی بعد از
 * اولین خرید (Salon::isTrialSmsQuotaInEffect). null = هیچ‌وقت آزمایشی نداشته (سالن‌های ساخته‌شده
 * توسط سوپرادمین، یا ثبت‌نام با SUBSCRIPTION_TRIAL_DAYS=0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }
};
