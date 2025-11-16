<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminNewBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $specialistName = $this->booking->specialist->name ?? 'نامشخص';
        $serviceName = $this->booking->service->name ?? 'نامشخص';
        $userName = $this->booking->user->name ?? 'کاربر ناشناس';

        $time = function_exists('verta')
            ? verta($this->booking->booking_time)->format('Y/m/d H:i')
            : $this->booking->booking_time->format('Y/m/d H:i');

        $link = route('admin.bookings.show', $this->booking->id);

        return [
            'type' => 'new_booking_admin',
            'booking_id' => $this->booking->id,
            'message' => "نوبت جدید ثبت شد: {$serviceName} با {$specialistName} توسط {$userName} در {$time}",
            'link' => $link,
        ];
    }
}
