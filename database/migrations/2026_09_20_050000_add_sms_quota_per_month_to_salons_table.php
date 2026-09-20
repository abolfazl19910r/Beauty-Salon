<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه» (تصمیم صریح ابوالفضل، ۲۰۲۶-۰۹-۲۰، بخشی از محور «۱. پرداخت
 * آنلاین و صورتحساب» — پیاده‌سازی همان جلسه‌ای که این ستون اضافه شد): هر سالن یک سقف پیامک
 * ماهانه دارد؛ وقتی تمام شد، ارسال پیامک (برای همه‌ی انواع — نوبت، یادآوری، ورود و...) متوقف
 * می‌شه و به‌جاش یک نوتیفیکیشن به ادمین‌های همون سالن و به سوپرادمین می‌ره که شارژ پیامک تموم
 * شده. مقدار پیش‌فرض از config('billing.sms_quota_per_month') خونده می‌شه (nullable اینجا
 * یعنی «از پیش‌فرض پلتفرم استفاده کن») — این ستون فقط برای override دستی روی یک سالن خاص است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->unsignedInteger('sms_quota_per_month')->nullable()->after('module_permissions');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('sms_quota_per_month');
        });
    }
};
