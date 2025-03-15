<?php

namespace App\Notifications;

use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    private $booking;
    private SMSService $smsService;

    public function __construct($booking)
    {
        $this->booking = $booking;
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
            'message' => 'یک نوبت جدید برای شما ثبت شده است',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'booking_time' => $this->booking->booking_time
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = sprintf(
            'متخصص گرامی، یک نوبت جدید:
مشتری: %s
تاریخ: %s
سرویس: %s
شماره تماس: %s',
            $this->booking->user->name,
            verta($this->booking->booking_time)->format('Y/m/d H:i'),
            $this->booking->service->name,
            $this->booking->user->phone
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
