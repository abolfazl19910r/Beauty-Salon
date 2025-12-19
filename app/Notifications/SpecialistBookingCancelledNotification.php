<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpecialistBookingCancelledNotification extends Notification
{

    protected Booking $booking;
    protected SMSService $smsService;
    protected string $cancelledBy;

    public function __construct(Booking $booking, string $cancelledBy)
    {
        $this->booking = $booking;
        $this->cancelledBy = $cancelledBy;
        $this->smsService = new SMSService();
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toDatabase($notifiable): array
    {
        $canceller = match($this->cancelledBy) {
            'user' => 'مشتری',
            'system' => 'سیستم (مانند پرداخت ناموفق)',
            default => 'ادمین یا متخصص'
        };
        return [
            'booking_id' => $this->booking->id,
            'message' => "نوبت توسط {$canceller} لغو شد.",
            'service_name' => $this->booking->service->name,
            'canceller' => $this->cancelledBy,
        ];
    }

    public function toSms($notifiable): bool
    {
        $persianDate = verta($this->booking->booking_time)->format('Y/m/d');
        $persianTime = verta($this->booking->booking_time)->format('H:i');
        $canceller = match($this->cancelledBy) {
            'user' => 'مشتری',
            'system' => 'سیستم',
            default => 'ادمین یا متخصص'
        };

        $message = sprintf(
            "%s عزیز، سلام 👋\n\n❌ هشدار لغو نوبت.\n\n👤 مشتری: %s\n📞 تماس: %s\n📋 سرویس: %s\n📅 تاریخ: %s - ساعت %s\n\n📝 لغو کننده: %s",
            $notifiable->name,
            $this->booking->user->name,
            $this->booking->user->phone,
            $this->booking->service->name,
            $persianDate,
            $persianTime,
            $canceller
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
