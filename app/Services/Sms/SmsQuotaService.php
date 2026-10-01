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

    /**
     * واحد سهمیه «قطعه» است (۲۰۲۶-۰۹-۳۰، مثل فاکتور کاوه‌نگار؛ App\Support\SmsParts).
     */
    public function recordUsage(Salon $salon, int $parts = 1): void
    {
        $this->usageRow($salon)->increment('used_count', max(1, $parts));
    }

    /** درصدی از سهمیه‌ی ماه که با رد شدنش یک بار در ماه به مدیر سالن هشدار داده می‌شود (۲۰۲۶-۰۹-۳۰) */
    public const WARNING_RATIO = 0.8;

    /**
     * مصرف $parts قطعه: اول از سهمیه‌ی ماه، کسری از اعتبار خریده‌شده‌ی سالن (salons.sms_credit، منقضی نمی‌شود). اگر جمع
     * باقی‌مانده‌ی ماه و اعتبار کافی نباشد هیچ چیز ثبت نمی‌شود و false برمی‌گردد — پیامک کامل مسدود است، نصفه نمی‌رود.
     * کم کردن اعتبار شرطی و اتمی است (sms_credit >= کسری) تا دو worker هم‌زمان اعتبار را منفی نکنند.
     */
    public function consume(Salon $salon, int $parts): bool
    {
        $parts = max(1, $parts);
        $fromMonth = min($parts, $this->remaining($salon));
        $fromCredit = $parts - $fromMonth;

        if ($fromCredit > 0) {
            $taken = Salon::withoutGlobalScopes()->whereKey($salon->id)
                ->where('sms_credit', '>=', $fromCredit)
                ->decrement('sms_credit', $fromCredit);
            if ($taken === 0) {
                return false;
            }
            $this->usageRow($salon)->increment('credit_used', $fromCredit);
        }

        if ($fromMonth > 0) {
            $this->recordUsage($salon, $fromMonth);
        }

        return true;
    }

    /** اعتبار خریده‌شده‌ی باقی‌مانده (قطعه) — از دیتابیس، نه مدلی که شاید قدیمی باشد */
    public function credit(Salon $salon): int
    {
        return (int) Salon::withoutGlobalScopes()->whereKey($salon->id)->value('sms_credit');
    }

    public function addCredit(Salon $salon, int $parts): void
    {
        Salon::withoutGlobalScopes()->whereKey($salon->id)->increment('sms_credit', max(0, $parts));
        // اگر این ماه قبلاً «سهمیه تمام شد» رفته، تمام شدن بعدی (این بار اعتبار) دوباره خبر داده شود
        $this->usageRow($salon)->update(['notified_at' => null]);
    }

    public function creditUsed(Salon $salon): int
    {
        return (int) $this->usageRow($salon)->credit_used;
    }

    /**
     * یک بار در هر ماه، وقتی مصرف از ۸۰٪ سهمیه‌ی ماه گذشت: true (و ثبت می‌کند که هشدار داده شد).
     */
    public function shouldWarnNearLimit(Salon $salon): bool
    {
        $quota = $this->quotaFor($salon);
        $usage = $this->usageRow($salon);

        if ($quota <= 0 || $usage->warned_at !== null || $usage->used_count < (int) ceil($quota * self::WARNING_RATIO)) {
            return false;
        }

        return SalonSmsUsage::whereKey($usage->id)->whereNull('warned_at')->update(['warned_at' => now()]) === 1;
    }

    public function recordOtp(Salon $salon): void
    {
        $this->usageRow($salon)->increment('otp_count');
    }

    public function otpCount(Salon $salon): int
    {
        return (int) $this->usageRow($salon)->otp_count;
    }

    public function usedCount(Salon $salon): int
    {
        return (int) $this->usageRow($salon)->used_count;
    }

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
