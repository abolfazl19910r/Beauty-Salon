<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Services\SMSService;

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
        return ['sms'];
    }

    public function toSms($notifiable)
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
