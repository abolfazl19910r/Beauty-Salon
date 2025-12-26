<?php

namespace App\Notifications;

use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    private $booking;
    private $needsApproval;

    public function __construct($booking, $needsApproval = false)
    {
        $this->booking = $booking;
        $this->needsApproval = $needsApproval;
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'یک نوبت جدید برای شما ثبت شده است',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'booking_time' => $this->booking->booking_time
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = sprintf(
            "%s عزیز، نوبت جدید ثبت شد:\n👤 مشتری: %s\n📅 تاریخ: %s\n⏰ ساعت: %s\n💇 سرویس: %s\n📞 تماس: %s",
            $notifiable->name,
            $this->booking->user->name,
            verta($this->booking->booking_time)->format('Y/m/d'),
            verta($this->booking->booking_time)->format('H:i'),
            $this->booking->service->name,
            $this->booking->user->phone
        );

        if ($this->needsApproval) {
            $message .= "\n\n⏳ نیاز به تایید شما";
        } else {
            $message .= "\n\n✅ تایید خودکار";
//            $message .= "\n🔗 مشاهده جزئیات:";
//            $message .= "\n" . $bookingLink;
        }

        return (new SMSService())->send($notifiable->phone, $message);
    }
}
