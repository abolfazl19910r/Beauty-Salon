<?php

namespace App\Providers\Event;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\Completed\BookingCompleted;
use App\Events\Payment\PaymentSucceeded;
use App\Events\User\NewUserRegistered;
use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Listeners\Admin\Booking\SendAdminBookingNotifications;
use App\Listeners\Admin\Payment\SendAdminPaymentNotification;
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
 * R-Events (follow-up 2): PaymentSucceeded now has a real listener.
 * - PaymentSucceeded ← dispatched from all 3 payment-success paths in
 *   PaymentController (process, processWithWallet, callback).
 *   SendAdminPaymentNotification notifies every admin (database + SMS) with
 *   amount, payment method, reference code, customer/specialist name, and a
 *   link to the booking.
 *
 * This is purely additive: it does NOT touch or replace
 * BookingObserver::handlePaymentStatusChange() (commission / specialist
 * wallet / loyalty points), which stays wired to the implicit
 * wasChanged('payment_status') model event for the reasons documented on
 * the PaymentSucceeded event class itself.
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
        PaymentSucceeded::class => [
            SendAdminPaymentNotification::class,
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
