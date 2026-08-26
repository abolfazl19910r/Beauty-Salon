<?php

namespace App\Notifications\Booking;

use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Notifications\Notification;

class CustomerBookingNotification extends Notification
{
    use RespectsNotificationSettings;

    private $booking;

    private SMSService $smsService;

    public function __construct($booking)
    {
        $this->booking = $booking;
        $this->smsService = new SMSService;
    }

    /**
     * ⭐ Fix: previously this notification also sent 'sms', duplicating the SMS the customer
     * receives moments later from BookingObserver::handlePaymentStatusChange() (sendCustomerConfirmationSMS/
     * sendCustomerPendingSMS) once payment_status actually becomes 'paid'. That second SMS is the accurate
     * one (correct status branch, real tracking code); this notification fires from
     * BookingService::createBooking() right when the booking row is created — i.e. before any payment has
     * happened — so its old SMS text ("با موفقیت ثبت و پرداخت شد") was also factually wrong at send time.
     * Kept as a 'database' (in-app) record only by default; the specialist side was never duplicated
     * this way (BookingNotification is only ever sent once, from the observer).
     * ⭐ Now routed through NotificationSettingService (admin can re-enable SMS/telegram for this
     * event from «تنظیمات اطلاع‌رسانی» if they explicitly want it, even though it's off by default).
     */
    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::BOOKING_CREATED_CUSTOMER, ['database', 'sms']);
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'رزرو شما ثبت شد',
            'service_name' => $this->booking->service->name,
            'specialist_name' => $this->booking->specialist->name,
            'booking_time' => $this->booking->booking_time,
            'payment_ref' => $this->booking->payment_reference,
            'total_price' => (float) $this->booking->service->price,
            'prepayment_amount' => (float) $this->booking->prepayment_amount,
            'remaining_amount' => $this->booking->remaining_amount,
        ];
    }

    public function toSms($notifiable): bool
    {
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
            number_format((float) $this->booking->service->price),
            number_format((float) $this->booking->prepayment_amount),
            number_format($this->booking->remaining_amount),
            $this->booking->payment_reference,
            config('app.salon_address')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
