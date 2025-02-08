<?php

namespace App\Notifications;

use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerBookingNotification extends Notification
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
            'نوبت شما با موفقیت ثبت و پرداخت شد:
تاریخ: %s
سرویس: %s
متخصص: %s
مبلغ پیش پرداخت: %s تومان
شماره پیگیری: %s
آدرس: %s',
            verta($this->booking->booking_time)->format('Y/m/d H:i'),
            $this->booking->service->name,
            $this->booking->specialist->name,
            number_format($this->booking->prepayment_amount),
            $this->booking->payment_ref,
            config('app.salon_address')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
