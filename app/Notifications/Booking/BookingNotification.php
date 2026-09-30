<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    public function __construct(
        private readonly Booking $booking,
        private readonly bool $needsApproval = false
    ) {}

    protected function settingsSalonId(): ?int
    {
        return $this->booking->salon_id;
    }

    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::BOOKING_CREATED_SPECIALIST, ['database', 'sms'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'یک نوبت جدید برای شما ثبت شده است',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'booking_time' => $this->booking->booking_time,
            'total_price' => (float) $this->booking->service->price,
            'prepayment_amount' => (float) $this->booking->prepayment_amount,
            'remaining_amount' => $this->booking->remaining_amount,
        ];
    }

    public function toSms($notifiable): bool
    {
        $confirmationLink = route('specialist.bookings.show', ['booking' => $this->booking->id]);

        $message = sprintf(
            "%s عزیز، نوبت جدید ثبت شد:\n👤 مشتری: %s\n📅 تاریخ: %s\n⏰ ساعت: %s\n💇 سرویس: %s\n📞 تماس: %s\n💰 قیمت کل خدمت: %s تومان\n✅ پیش‌پرداخت دریافتی از مشتری (از طریق سایت): %s تومان\n💵 باقی‌مانده (موقع نوبت مستقیماً از مشتری دریافت کنید): %s تومان",
            $notifiable->name,
            $this->booking->user->name,
            verta($this->booking->booking_time)->format('Y/m/d'),
            verta($this->booking->booking_time)->format('H:i'),
            $this->booking->service->name,
            $this->booking->user->phone,
            number_format((float) $this->booking->service->price),
            number_format((float) $this->booking->prepayment_amount),
            number_format($this->booking->remaining_amount)
        );

        if ($this->needsApproval) {
            $message .= "\n\n⏳ نیاز به تایید شما\n🔗 جهت تایید کلیک کنید:\n".$confirmationLink;
        } else {
            $message .= "\n\n✅ تایید خودکار";
        }

        return app(SMSService::class)->send($notifiable->phone, $message, $this->booking->salon_id);
    }
}
