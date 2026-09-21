<?php

namespace App\Services\Sms;

use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Repositories\Contracts\SalonSmsUsageRepositoryInterface;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه» (تصمیم صریح ابوالفضل، ۲۰۲۶-۰۹-۲۰). هدف: محافظت از اعتبار
 * Kavenegar پلتفرم (یک حساب مشترک برای همه‌ی سالن‌ها) در برابر یک سالن پرمصرف که کل بودجه‌ی
 * پیامک ماهانه‌اش را جلوتر از حد انتظار (config('billing.sms_quota_per_month'), مشتق از فرض
 * ۵۰ تا ۲۰۰ نوبت در ماه) مصرف می‌کند. واحد سقف: تعداد "پیامک منطقی" ارسال‌شده (هر فراخوانی
 * موفق SMSService::send()/sendTemplate() با salon_id، صرف‌نظر از تعداد قطعات واقعی پیامک) —
 * نه بازه‌ی اشتراک (۱/۳/۶/۱۲ ماهه)، بلکه ماه تقویمی، چون این یک بودجه‌ی ماهانه‌ی تکرارشونده است.
 */
class SmsQuotaService
{
    public function __construct(private readonly SalonSmsUsageRepositoryInterface $salonSmsUsageRepository) {}

    public function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    public function quotaFor(Salon $salon): int
    {
        return $salon->sms_quota_per_month ?? (int) config('billing.sms_quota_per_month');
    }

    public function usageRow(Salon $salon): SalonSmsUsage
    {
        return $this->salonSmsUsageRepository->firstOrCreateForPeriod($salon->id, $this->currentPeriod());
    }

    public function hasQuotaRemaining(Salon $salon): bool
    {
        return $this->usageRow($salon)->used_count < $this->quotaFor($salon);
    }

    public function remaining(Salon $salon): int
    {
        return max(0, $this->quotaFor($salon) - $this->usageRow($salon)->used_count);
    }

    public function recordUsage(Salon $salon): void
    {
        $this->usageRow($salon)->increment('used_count');
    }

    /**
     * فقط یک‌بار در هر دوره true برمی‌گرداند (وقتی سقف برای اولین بار همان ماه رد شده) — تا
     * نوتیفیکیشن اتمام شارژ برای هر پیامک بعدیِ مسدودشده دوباره ارسال نشود.
     */
    public function shouldNotifyExhaustion(Salon $salon): bool
    {
        $usage = $this->usageRow($salon);

        if ($usage->notified_at !== null) {
            return false;
        }

        $usage->update(['notified_at' => now()]);

        return true;
    }
}
