<?php

namespace App\Listeners;

use Illuminate\Console\Scheduling\Schedule;
use App\Events\ScheduleBookingTasksEvent;
use App\Jobs\CancelUnpaidBookings;

class RegisterBookingSchedule
{
    /**
     * Handle the event.
     */
    public function handle(ScheduleBookingTasksEvent $event): void
    {
        $schedule = app(Schedule::class);

        $schedule->job(new CancelUnpaidBookings())
            ->everyFiveMinutes()
            ->name('cancel-unpaid-bookings')
            ->withoutOverlapping()
            ->onOneServer();
    }
}
