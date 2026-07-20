<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CancelUnpaidBookings;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CancelUnpaidBookings())
    ->everyFiveMinutes()
    ->name('cancel-unpaid-bookings')
    ->withoutOverlapping()
    ->onOneServer();

// Note: The actual wallet:settle-pending shedule is registered in bootstrap/app.php.
// Previously this command was registered both here and in bootstrap/app.php (double execution every night at 01:00).
