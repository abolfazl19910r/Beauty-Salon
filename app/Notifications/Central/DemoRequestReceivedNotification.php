<?php

namespace App\Notifications\Central;

use App\Models\DemoRequest;
use App\Notifications\Concerns\SendsSmsOnSmsQueue;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * درخواست دموی تازه از صفحه‌ی فروش (تصمیم ۲۰۲۶-۱۰-۱۴): پیامک به شماره‌ی همه‌ی سوپرادمین‌ها و ایمیل به «ایمیل گیرنده‌ی درخواست‌های
 * دمو» تنظیمات پلتفرم (جدول users ستون ایمیل ندارد؛ ورود فقط با موبایل است). پیامک هزینه‌ی پلتفرم است — بدون salon_id، از سهمیه‌ی
 * هیچ سالنی کم نمی‌شود. فقط برای ردیف تازه فرستاده می‌شود (درخواست تکراری همان شماره در ۲۴ ساعت، و تله‌ی ربات، اعلان ندارند).
 */
class DemoRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsSmsOnSmsQueue;

    public function __construct(public readonly DemoRequest $demoRequest) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['sms'];
    }

    public function toSms(object $notifiable): bool
    {
        return app(SMSService::class)->send($notifiable->phone, self::smsText($this->demoRequest));
    }

    /** یک قطعه تا جای ممکن: نام، سالن، شماره */
    public static function smsText(DemoRequest $d): string
    {
        $salon = $d->salon_name ? ' — '.mb_substr($d->salon_name, 0, 20) : '';

        return sprintf('درخواست دمو: %s%s، %s. %s', mb_substr($d->name, 0, 24), $salon, $d->phone, config('brand.name'));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->demoRequest;

        return (new MailMessage)
            ->subject('درخواست دموی تازه — '.config('brand.name'))
            ->greeting('درخواست دموی تازه')
            ->line('نام: '.$d->name)
            ->line('سالن: '.($d->salon_name ?: '—'))
            ->line('نوع سالن: '.($d->typeLabel() ?? '—'))
            ->line('موبایل: '.$d->phone)
            ->action('فهرست درخواست‌های دمو', route('superadmin.demo-requests.index'));
    }
}
