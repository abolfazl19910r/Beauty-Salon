<?php

namespace App\Providers\Event;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\Completed\BookingCompleted;
use App\Events\User\NewUserRegistered;
use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Listeners\Admin\Booking\SendAdminBookingNotifications;
use App\Listeners\Admin\Withdrawal\SendAdminWithdrawalNotification;
use App\Listeners\Booking\Cancellation\SendBookingCancellationNotifications;
use App\Listeners\Booking\Completion\SendBookingCompletionNotifications;
use App\Listeners\User\SendNewUserNotifications;
use App\Listeners\Withdrawal\Approved\SendWithdrawalApprovedNotification;
use App\Listeners\Withdrawal\Rejected\SendWithdrawalRejectedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * R-Events (follow-up): BookingCompleted added.
 * - BookingCompleted ← SpecialistBookingManagementController::markAsCompleted()
 *   Replaces the two direct calls that used to live inline in that method
 *   (ReviewService::sendReviewRequest() + BookingStatusUpdated notify).
 *
 * PaymentSucceeded (App\Events\Payment\PaymentSucceeded) is intentionally
 * NOT listed here: it is dispatched from all 3 payment-success paths in
 * PaymentController as a purely additive extension point for future
 * consumers, but has no listener yet. It does not replace or touch
 * BookingObserver::handlePaymentStatusChange(), which stays wired to the
 * implicit wasChanged('payment_status') model event — see the docblock on
 * the PaymentSucceeded event class for the reasoning.
 *
 * Everything else unchanged from the previous version of this file.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NewUserRegistered::class => [
            SendNewUserNotifications::class,
        ],
        BookingCreated::class => [
            SendAdminBookingNotifications::class,
        ],
        BookingCancelled::class => [
            SendBookingCancellationNotifications::class,
        ],
        BookingCompleted::class => [
            SendBookingCompletionNotifications::class,
        ],
        WithdrawalRequested::class => [
            SendAdminWithdrawalNotification::class,
        ],
        WithdrawalApproved::class => [
            SendWithdrawalApprovedNotification::class,
        ],
        WithdrawalRejected::class => [
            SendWithdrawalRejectedNotification::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
