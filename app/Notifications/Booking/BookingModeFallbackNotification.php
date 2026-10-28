<?php

namespace App\Notifications\Booking;

use App\Models\Salon;
use App\Notifications\Concerns\SendsSmsOnSmsQueue;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * هشدار به مالک سالن (بسته‌ی 5ه، تصمیم ۲): «رزرو با پیش‌پرداخت» بی درگاه فعال یا بی امکان پرداخت آنلاین پلن ممکن نیست؛ سالن خودکار به
 * «بدون پیش‌پرداخت» برگشت و رزرو قطع نشد. اعلان داخل پنل + پیامک از سهمیه‌ی سالن (با سهمیه‌ی تمام‌شده هم، مثل «نیاز به بررسی»).
 */
class BookingModeFallbackNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsSmsOnSmsQueue;

    public function __construct(
        public readonly int $salonId,
        public readonly string $reason,
        public readonly int $waivedBookings = 0,
    ) {}

    public function notificationSalonId(?object $notifiable = null): ?int
    {
        return $this->salonId;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'sms'];
    }

    public function message(): string
    {
        $text = sprintf('رزرو با پیش‌پرداخت متوقف شد: %s سالن خودکار به «بدون پیش‌پرداخت» برگشت و مشتری‌ها همچنان نوبت می‌گیرند.', $this->reason);
        if ($this->waivedBookings > 0) {
            $text .= sprintf(' %s نوبت در انتظار پرداخت حالا بدون پیش‌پرداخت و منتظر تأیید است.', to_persian_num((string) $this->waivedBookings));
        }

        return $text;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_mode_fallback_admin',
            'salon_id' => $this->salonId,
            'message' => $this->message(),
            'link' => route('admin.booking-policy.edit', [], false),
        ];
    }

    public function toSms(object $notifiable): bool
    {
        $salonName = Salon::whereKey($this->salonId)->value('name') ?? '';
        $text = sprintf('%s: رزرو با پیش‌پرداخت متوقف شد (%s) و رزرو بدون پیش‌پرداخت ادامه دارد. پنل مدیریت ← نحوه‌ی رزرو', $salonName, rtrim($this->reason, '.'));

        return app(SMSService::class)->sendChargedWithoutLimit($notifiable->phone, $text, $this->salonId);
    }
}
