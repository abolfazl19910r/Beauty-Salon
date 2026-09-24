<?php

namespace App\Notifications\Salon;

use App\Models\Salon;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * ⭐ پیامک خوش‌آمد به مالک سالن تازه‌ثبت‌نام‌شده (۲۰۲۶-۰۹-۲۵) — بعد از تایید موبایل در ثبت‌نام خودکار
 * (SalonSignupController::verify)، یک‌بار. پیام خودِ پلتفرمه، پس مثل SmsQuotaExhaustedNotification
 * عمداً بدون salon_id فرستاده می‌شه و از سهمیه‌ی پیامک سالن کم نمی‌کنه. صف‌دار، تا ثبت‌نام منتظر
 * سرویس پیامک نمونه. سالن‌هایی که سوپرادمین می‌سازه این پیامک رو نمی‌گیرن.
 */
class SalonWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private readonly string $salonName;

    private readonly string $publicUrl;

    private readonly string $adminUrl;

    /** آدرس‌ها همین‌جا (داخل درخواست ثبت‌نام) ساخته می‌شن، نه در worker صف که ممکنه host دیگه‌ای داشته باشه. */
    public function __construct(Salon $salon, private readonly int $trialDays)
    {
        $this->salonName = $salon->name;
        $this->publicUrl = $salon->publicUrl();
        $this->adminUrl = route('admin.home');
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

        if ($this->trialDays > 0) {
            $lines[] = "دوره‌ی آزمایشی رایگان {$this->trialDays} روزه فعال است.";
        }

        $lines[] = 'قدم اول: از «درگاه‌های پرداخت» درگاه سالن را اضافه کنید.';

        return implode("\n", $lines);
    }

    public function toSms(object $notifiable): bool
    {
        return (new SMSService)->send($notifiable->phone, $this->message());
    }
}
