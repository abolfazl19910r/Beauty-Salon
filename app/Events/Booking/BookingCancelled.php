<?php

namespace App\Events\Booking;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched from the three places that can cancel a booking:
 * - BookingService::cancelBooking()                          (cancelledBy = 'customer')
 * - SpecialistBookingManagementController::cancel()           (cancelledBy = 'specialist')
 * - AdminBookingService::handlePostUpdateSideEffects()        (cancelledBy = 'admin')
 *
 * R-Observers addendum: admin cancellations now set cancelled_by='admin' on the model itself
 * (previously deliberately withheld — see git history — because RefundService attempted a real
 * gateway refund and setting cancelled_by='admin' would have ALSO triggered the observer's
 * wallet-credit branch, double-refunding). RefundService's gateway-refund call was found to be
 * completely broken (called a PaymentService::refund() method that doesn't exist, an uncaught
 * \Error on every admin cancellation of a paid booking) and is no longer used; admin
 * cancellations now go through the same wallet-credit path as customer/specialist ones.
 */
class BookingCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $cancelledBy,
    ) {}
}
