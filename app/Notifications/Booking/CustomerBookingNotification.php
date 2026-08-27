<?php

namespace App\Notifications\Booking;

use Illuminate\Notifications\Notification;

class CustomerBookingNotification extends Notification
{
    private $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * ⭐ Fix: previously this notification also sent 'sms' (either always, or — after an earlier
     * fix — behind an admin-configurable toggle). Per explicit user decision, this SMS is now
     * removed entirely (not just defaulted off), because it fires from
     * BookingService::createBooking() right when the booking row is created — i.e. before any
     * payment has happened — and its text ("با موفقیت ثبت و پرداخت شد") was factually wrong at
     * send time; it was always redundant with the real, accurate SMS sent moments later from
     * BookingObserver::handlePaymentStatusChange() (sendCustomerConfirmationSMS/sendCustomerPendingSMS)
     * once payment_status actually becomes 'paid'. Kept as a 'database' (in-app) record only — the
     * event key/toggle for this SMS has also been removed entirely from
     * NotificationEvents/the admin settings panel, not merely defaulted off.
     */
    public function via($notifiable): array
    {
        return ['database'];
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
}
