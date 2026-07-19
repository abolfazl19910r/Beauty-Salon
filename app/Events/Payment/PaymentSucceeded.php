<?php

namespace App\Events\Payment;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched from all 3 payment-success paths in PaymentController
 * (process, processWithWallet, callback).
 *
 * Consumed by SendAdminPaymentNotification (admin database+SMS receipt
 * notification) — see App\Providers\Event\EventServiceProvider.
 *
 * Deliberately does NOT replace or touch the existing
 * commission/wallet/loyalty-points logic in
 * BookingObserver::handlePaymentStatusChange(), which stays triggered
 * implicitly via Eloquent's wasChanged('payment_status') check.
 *
 * That implicit trigger is intentionally left alone: it fires automatically
 * for ANY code path that sets payment_status='paid' on the model, with no
 * possibility of a call site "forgetting" to dispatch it manually. Moving
 * this financial logic to depend on an explicit event dispatch (one that
 * every current and future payment path would have to remember to call)
 * would trade that safety for architectural consistency alone — not a
 * trade worth making for money-moving code. If that migration is ever
 * wanted, it belongs in a dedicated R-Observers pass, not bundled in here.
 */
class PaymentSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {
    }
}
