<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BookingRescheduledNotification extends Notification
{
    private Booking $booking;
    private string|Carbon $oldTime;
    private SMSService $smsService;

    /**
     *
     * @param Booking $booking
     * @param Carbon|string $oldTime
     * @return void
     */
    public function __construct(Booking $booking, $oldTime)
    {
        $this->booking = $booking;
        $this->oldTime = $oldTime;
        $this->smsService = new SMSService();
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'زمان نوبت تغییر کرد',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'old_time' => $this->oldTime,
            'new_time' => $this->booking->booking_time
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = sprintf(
            'تغییر زمان نوبت:
خدمت: %s
زمان قبلی: %s
زمان جدید: %s
متخصص: %s',
            $this->booking->service->name,
            verta($this->oldTime)->format('Y/m/d H:i'),
            verta($this->booking->booking_time)->format('Y/m/d H:i'),
            $this->booking->specialist->name
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
