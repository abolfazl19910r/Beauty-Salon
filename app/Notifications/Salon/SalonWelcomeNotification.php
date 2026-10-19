<?php

namespace App\Notifications\Salon;

use App\Models\Salon;
use App\Notifications\Concerns\SendsSmsOnSmsQueue;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * ⭐ پیامک خوش‌آمد به مالک سالن تازه‌ثبت‌نام‌شده (۲۰۲۶-۰۹-۲۵) — بعد از تایید موبایل در ثبت‌نام خودکار
 * (SalonSignupController::verify)، یک‌بار. پیام خودِ پلتفرمه، پس مثل SmsQuotaExhaustedNotification
 * عمداً بدون salon_id فرستاده می‌شه و از سهمیه‌ی پیامک سالن کم نمی‌کنه. صف‌دار، تا ثبت‌نام منتظر
 * سرویس پیامک نمونه. سالن‌هایی که سوپرادمین می‌سازه این پیامک رو نمی‌گیرن.
 *
 * بسته‌ی «ح»، ۳ (۲۰۲۶-۱۰-۱۹): متن از وضعیت واقعی اشتراک در لحظه‌ی ساختن پیام — اشتراک پولی ← نام پلن و تاریخ پایان،
 * آزمایشی ← روزهای آزمایشی. ثبت‌نام با «خرید» آن را تا نتیجه‌ی پرداخت نگه می‌دارد (App\Services\SalonSignup\SalonWelcome).
 */
class SalonWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsSmsOnSmsQueue;

    private readonly string $salonName;

    private readonly string $publicUrl;

    private readonly string $adminUrl;

    /** پلن پولی فعال: [نام پلن، تاریخ پایان شمسی] */
    private readonly ?array $paidPlan;

    /**
     * آدرس‌ها همین‌جا (داخل درخواست ثبت‌نام) ساخته می‌شن، نه در worker صف که ممکنه host دیگه‌ای داشته باشه؛ مهلت پشتیبان
     * (SendPendingSalonWelcomeJob) آدرس‌های ساخته‌شده در همان درخواست را می‌دهد.
     */
    public function __construct(Salon $salon, private readonly int $trialDays, ?string $publicUrl = null, ?string $adminUrl = null)
    {
        $this->salonName = $salon->name;
        $this->publicUrl = $publicUrl ?? $salon->publicUrl();
        $this->adminUrl = $adminUrl ?? route('admin.home');
        $this->paidPlan = $salon->hasPaidInvoice() && $salon->subscription_ends_at?->isFuture()
            ? [$salon->plan?->name, jalali_date($salon->subscription_ends_at)]
            : null;
    }

    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    public function message(): string
    {
        $lines = [
            'به '.config('brand.name').' خوش آمدید!',
            "سالن «{$this->salonName}» ساخته شد.",
            'آدرس رزرو برای مشتری‌ها:',
            $this->publicUrl,
            'پنل مدیریت:',
            $this->adminUrl,
        ];

        if ($this->paidPlan !== null) {
            [$plan, $until] = $this->paidPlan;
            $lines[] = ($plan ? "پلن «{$plan}»" : 'اشتراک سالن')." تا {$until} فعال است.";
        } elseif ($this->trialDays > 0) {
            $lines[] = "دوره‌ی آزمایشی رایگان {$this->trialDays} روزه فعال است.";
        }

        $lines[] = 'قدم اول: از «درگاه‌های پرداخت» درگاه سالن را اضافه کنید.';

        return implode("\n", $lines);
    }

    public function toSms(object $notifiable): bool
    {
        // خرج پلتفرم (تصمیم ۲۰۲۶-۰۹-۳۰): عمداً بدون salon_id — از سهمیه کم نمی‌شود و با سهمیه‌ی تمام‌شده هم می‌رود
        return app(SMSService::class)->send($notifiable->phone, $this->message());
    }
}
