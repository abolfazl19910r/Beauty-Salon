<?php

namespace App\Providers\Event;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCreated;
use App\Events\User\NewUserRegistered;
use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Listeners\Admin\Booking\SendAdminBookingNotifications;
use App\Listeners\Admin\Withdrawal\SendAdminWithdrawalNotification;
use App\Listeners\Booking\Cancellation\SendBookingCancellationNotifications;
use App\Listeners\User\SendNewUserNotifications;
use App\Listeners\Withdrawal\Approved\SendWithdrawalApprovedNotification;
use App\Listeners\Withdrawal\Rejected\SendWithdrawalRejectedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * R-Events: This provider was not registered in bootstrap/providers.php
 * at all before this phase — meaning the entire $listen array below (including the BookingCreated and
 * NewUserRegistered mappings) was never booted. Now the provider is registered (look at
 * bootstrap/providers.php) and the events are actually dispatched from somewhere:
 * - BookingCreated ← App\Observers\Booking\BookingObserver::created()
 * - NewUserRegistered ← App\Http\Controllers\Auth\RegisteredUserController::store()
 * - BookingCancelled ← App\Services\Booking\BookingService / SpecialistBookingManagementController / AdminBookingService
 * - WithdrawalRequested ← App\Services\Specialist\SpecialistWalletService::createWithdrawal()
 * - WithdrawalApproved ← App\Services\Admin\Wallet\WalletAdminService::approveWithdrawal()
 * - WithdrawalRejected ← App\Services\Admin\Wallet\WalletAdminService::rejectWithdrawal()
 *
 * Removed from the previous version of this file (when it was still App\Providers\EventServiceProvider):
 * - ScheduleBookingTasksEvent / RegisterBookingSchedule: Nowhere in the project
 * was it possible to dispatch; the job (CancelUnpaidBookings) itself was already scheduled directly in
 * routes/console.php — this event/listener was completely dead code.
 * - ReminderScheduleEvent / RegisterReminderSchedule: Scheduling mechanism
 * was the bookings:send-reminders command via event+config-flag, which never really worked because this
 * provider never booted. It was replaced by scheduling
 * directly in bootstrap/app.php.
 * - DiscountCode::observe() removed duplicate boot(); the same observe was already registered in
 * AppServiceProvider.
 *
 * Intentionally not added in this phase:
 * - SpecialistLeaveApproved: LeaveService already sends LeaveStatusNotification directly and correctly
 * .
 * - PaymentSucceeded / BookingCompleted: Intentionally postponed to the next phase.
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
