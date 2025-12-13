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
