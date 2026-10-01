<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * واحد سهمیه‌ی پیامک از «پیام» به «قطعه» (تصمیم ۲۰۲۶-۰۹-۳۰). پیش‌فرض ۱۵۰۰ پیام → ۲۰۰۰ قطعه و آزمایشی ۳۰۰ → ۴۰۰ (هر دو × ۴/۳).
 *
 * داده‌ی موجود با همان نسبت تبدیل می‌شود تا هیچ سالنی در لحظه‌ی انتشار ناگهان قطع یا شارژ نشود: هر سالن همان «کسر مصرف‌شده»ی
 * ماه جاری را نگه می‌دارد (مثلاً ۷۵۰ از ۱۵۰۰ → ۱۰۰۰ از ۲۰۰۰)، و سهمیه‌های اختصاصی (از جمله ۳۰۰ آزمایشی → ۴۰۰، که
 * Salon::isTrialSmsQuotaInEffect با TRIAL_SMS_QUOTA مقایسه می‌کند) هم با همان نسبت. ماه‌های گذشته فقط تاریخچه‌اند و دست نمی‌خورند.
 * ⚠️ .env سرور هم باید SMS_QUOTA_PER_MONTH=2000 و TRIAL_SMS_QUOTA=400 شود.
 */
return new class extends Migration
{
    private function scale(float $factor): void
    {
        $period = now()->format('Y-m');

        DB::table('salon_sms_usages')->where('period', $period)->where('used_count', '>', 0)
            ->update(['used_count' => DB::raw('ROUND(used_count * '.$factor.')')]);

        DB::table('salons')->whereNotNull('sms_quota_per_month')
            ->update(['sms_quota_per_month' => DB::raw('ROUND(sms_quota_per_month * '.$factor.')')]);
    }

    public function up(): void
    {
        $this->scale(4 / 3);
    }

    public function down(): void
    {
        $this->scale(3 / 4);
    }
};
