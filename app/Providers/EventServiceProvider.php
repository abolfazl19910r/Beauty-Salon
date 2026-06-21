<?php

namespace App\Providers;

use App\Events\ScheduleBookingTasksEvent;
use App\Listeners\RegisterBookingSchedule;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Observers\BookingObserver;
use App\Observers\DiscountCodeObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\NewUserRegistered::class => [
            \App\Listeners\SendNewUserNotifications::class,
        ],
        \App\Events\BookingCreated::class => [
            \App\Listeners\SendAdminBookingNotifications::class,
        ],
        ScheduleBookingTasksEvent::class => [
            RegisterBookingSchedule::class,
        ],
        \App\Events\ReminderScheduleEvent::class => [
            \App\Listeners\RegisterReminderSchedule::class,
        ],
    ];

    public function boot(): void
    {
        DiscountCode::observe(DiscountCodeObserver::class);
        if (config('app.register_reminder_schedule')) {
            $this->app->booted(function () {
                $schedule = app(Schedule::class);

                $schedule->command('bookings:send-reminders')
                    ->dailyAt('18:00')
                    ->timezone('Asia/Tehran');
            });
        }
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
