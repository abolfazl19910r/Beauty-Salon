<?php

namespace App\Listeners;

use Illuminate\Console\Scheduling\Schedule;
use App\Events\ReminderScheduleEvent;

class RegisterReminderSchedule
{
    public function handle(ReminderScheduleEvent $event): void
    {
        config(['app.register_reminder_schedule' => true]);
    }
}

