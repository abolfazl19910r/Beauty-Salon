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
        $totalPrice = (float) $this->booking->service->price;
        $prepayment = (float) $this->booking->prepayment_amount;

        return [
            'booking_id' => $this->booking->id,
            'message' => 'رزرو شما با موفقیت ثبت و تأیید شد',
            'service_name' => $this->booking->service->name,
            'specialist_name' => $this->booking->specialist->name,
            'booking_time' => $this->booking->booking_time,
            'payment_ref' => $this->booking->payment_reference,
            'total_price' => $totalPrice,
            'prepayment_amount' => $prepayment,
            'remaining_amount' => max(0, $totalPrice - $prepayment),
        ];
    }

    public function toSms($notifiable): bool
    {
        $totalPrice = (float) $this->booking->service->price;
        $prepayment = (float) $this->booking->prepayment_amount;
        $remaining = max(0, $totalPrice - $prepayment);

        $message = sprintf(
            'نوبت شما با موفقیت ثبت و پرداخت شد:
تاریخ: %s
سرویس: %s
متخصص: %s
مبلغ کل خدمت: %s تومان
مبلغ پیش پرداخت: %s تومان
باقی‌مانده (موقع نوبت پرداخت می‌کنید): %s تومان
شماره پیگیری: %s
آدرس: %s',
            verta($this->booking->booking_time)->format('Y/m/d H:i'),
            $this->booking->service->name,
            $this->booking->specialist->name,
            number_format($totalPrice),
            number_format($prepayment),
            number_format($remaining),
            $this->booking->payment_reference,
            config('app.salon_address')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
