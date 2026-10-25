<?php

namespace App\Notifications\SuperAdmin;

use App\Notifications\Concerns\SendsSmsOnSmsQueue;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * یادآوری پیامکی شکایت‌های بی‌پاسخ به ابرمدیرها (بسته‌ی مجوز 5د؛ دستورالعمل اینماد ۳-۸: پاسخ ظرف ۷۲ ساعت، ۵ شکایت بی‌پاسخ = تعلیق
 * اینماد و مسدود شدن درگاه‌ها). هزینه‌ی پلتفرم، بی salon_id. complaints:remind
 */
class ComplaintDueNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsSmsOnSmsQueue;

    public function __construct(public readonly int $overdue, public readonly int $dueSoon) {}

    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    public function toSms(object $notifiable): bool
    {
        return app(SMSService::class)->send($notifiable->phone, self::smsText($this->overdue, $this->dueSoon));
    }

    public static function smsText(int $overdue, int $dueSoon): string
    {
        $parts = array_filter([
            $overdue ? to_persian_num((string) $overdue).' شکایت از مهلت ۷۲ ساعت گذشته' : null,
            $dueSoon ? to_persian_num((string) $dueSoon).' شکایت کمتر از ۲۴ ساعت مهلت دارد' : null,
        ]);

        return implode('، ', $parts).'. پنل ابرمدیر ← تیکت‌ها و شکایت‌ها. '.config('brand.name');
    }
}
