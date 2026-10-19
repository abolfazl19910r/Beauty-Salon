<?php

namespace App\Services\SalonSignup;

use App\Jobs\SendPendingSalonWelcomeJob;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\Salon\SalonWelcomeNotification;

/**
 * پیامک خوش‌آمد مالک سالن تازه (بسته‌ی «ح»، ۳، تصمیم ۲۰۲۶-۱۰-۱۹).
 *
 * ثبت‌نام عادی: همان لحظه. ثبت‌نام با «خرید» (کارت پلن لندینگ): سالن اول آزمایشی ساخته می‌شود؛ پیامکی که آن لحظه می‌رفت می‌گفت
 * «دوره‌ی آزمایشی فعال است» درحالی‌که مالک داشت کلینیک می‌خرید. حالا تا نتیجه‌ی پرداخت صبر می‌کند و متنش وضعیت واقعی است:
 * پرداخت موفق ← نام پلن و تاریخ پایان؛ ناموفق، اتصال نشده یا رهاشده (مهلت پشتیبان) ← متن آزمایشی (سالن واقعاً آزمایشی می‌ماند).
 */
class SalonWelcome
{
    /** اگر مالک از صفحه‌ی بانک برنگشت، پیامک بعد از این مدت با وضعیت همان لحظه می‌رود */
    public const FALLBACK_MINUTES = 30;

    public function __construct(private readonly SalonSignupService $signup) {}

    public function sendNow(Salon $salon, User $owner): void
    {
        $owner->notify($this->notification($salon));
    }

    public function deferUntilPayment(Salon $salon, User $owner): void
    {
        $salon->forceFill(['welcome_sms_pending_at' => now()])->save();

        // آدرس‌ها همین‌جا (داخل درخواست ثبت‌نام) ساخته می‌شوند، نه در worker صف که ممکن است میزبان دیگری داشته باشد
        SendPendingSalonWelcomeJob::dispatch($salon->id, $owner->id, $salon->publicUrl(), route('admin.home'))
            ->delay(now()->addMinutes(self::FALLBACK_MINUTES));
    }

    /**
     * پیامک نگه‌داشته را با وضعیت همین لحظه می‌فرستد؛ فقط اولین فراخوانی (نتیجه‌ی پرداخت یا مهلت پشتیبان) — بقیه false.
     */
    public function sendPending(Salon $salon, ?User $owner = null, ?string $publicUrl = null, ?string $adminUrl = null): bool
    {
        $claimed = Salon::query()->whereKey($salon->id)->whereNotNull('welcome_sms_pending_at')->update(['welcome_sms_pending_at' => null]);
        if ($claimed === 0) {
            return false;
        }

        $salon = $salon->fresh();
        $owner ??= $salon->owner();
        if (! $owner) {
            return false;
        }

        $owner->notify($this->notification($salon, $publicUrl, $adminUrl));

        return true;
    }

    private function notification(Salon $salon, ?string $publicUrl = null, ?string $adminUrl = null): SalonWelcomeNotification
    {
        return new SalonWelcomeNotification(
            $salon,
            $salon->isOnTrial() ? $this->signup->trialDays() : 0,
            publicUrl: $publicUrl,
            adminUrl: $adminUrl,
        );
    }
}
