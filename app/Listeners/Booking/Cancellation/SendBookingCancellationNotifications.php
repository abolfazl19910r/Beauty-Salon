<?php

namespace App\Listeners\Booking\Cancellation;

use App\Events\Booking\BookingCancelled;
use App\Notifications\Booking\BookingStatusUpdated;
use App\Notifications\Booking\SpecialistBookingCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends the in-app/database notifications for a cancelled booking.
 *
 * Deliberately does NOT send SMS here: BookingObserver::sendCancellationSMS()
 * already sends the cancellation SMS to both customer and specialist for
 * every cancellation, unconditionally, regardless of who cancelled it.
 * Sending SMS here too would duplicate that message.
 */
class SendBookingCancellationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking;

        try {
            $booking->user?->notify(new BookingStatusUpdated(
                $booking,
                'cancelled',
                $booking->cancellation_reason,
            ));
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن لغو نوبت به مشتری', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $booking->specialist?->notify(new SpecialistBookingCancelledNotification(
                $booking,
                $event->cancelledBy,
            ));
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن لغو نوبت به متخصص', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
