<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Features\Feature;
use App\Support\Sms\SmsText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * یادآوری پیش‌پرداخت که مدیر سالن دستی از فهرست نوبت‌ها می‌فرستد (بسته‌ی ۲ ظاهری، تصمیم و متن تأییدشده ۲۰۲۶-۱۱-۲۵).
 * پیامک از سهمیه‌ی سالن (SmsCustomer) و در صف sms؛ همان پیام در صندوق اعلان مشتری هم می‌ماند.
 */
class BookingPaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, SerializesModels;

    public function __construct(private Booking $booking, private string $link) {}

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function notificationSalonId(?object $notifiable = null): ?int
    {
        return $this->booking->salon_id;
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'نوبت شما منتظر پیش‌پرداخت است',
            'service_name' => $this->booking->service?->name,
            'booking_time' => $this->booking->booking_time,
            'url' => $this->link,
        ];
    }

    public function toSms($notifiable): bool
    {
        return app(SMSService::class)->send($notifiable->phone, SmsText::bookingPaymentReminder($this->booking, $this->link),
            $this->booking->salon_id, Feature::SmsCustomer);
    }
}
