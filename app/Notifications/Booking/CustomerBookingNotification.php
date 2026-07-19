<?php

namespace App\Notifications\Booking;

use App\Services\SMSService;
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
        return ['database', 'sms'];
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'رزرو شما با موفقیت ثبت و تأیید شد',
            'service_name' => $this->booking->service->name,
            'specialist_name' => $this->booking->specialist->name,
            'booking_time' => $this->booking->booking_time,
            'payment_ref' => $this->booking->payment_reference
        ];
    }

    public function toSms($notifiable): bool
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
            $this->booking->payment_reference,
            config('app.salon_address')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
