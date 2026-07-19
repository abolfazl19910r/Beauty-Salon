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
 * NOTE: $cancelledBy here is only used for notification wording — it is
 * deliberately NOT written back into $booking->cancelled_by for the admin
 * path, because that column already drives BookingObserver's wallet-refund
 * branches. Admin cancellations are refunded through the gateway via
 * RefundService instead; setting cancelled_by='admin' on the model would
 * make BOTH the observer's wallet-credit AND RefundService's gateway
 * refund fire for the same cancellation (double refund).
 */
class BookingCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $cancelledBy,
    ) {
    }
}
