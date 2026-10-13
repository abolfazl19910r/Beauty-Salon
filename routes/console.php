<?php

use App\Jobs\CancelUnpaidBookings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CancelUnpaidBookings)
    ->everyFiveMinutes()
    ->name('cancel-unpaid-bookings')
    ->withoutOverlapping()
    ->onOneServer();

// Note: The actual wallet:settle-pending shedule is registered in bootstrap/app.php.
// Previously this command was registered both here and in bootstrap/app.php (double execution every night at 01:00).

// انقضای امتیاز باشگاه مشتریان (LoyaltyLedger، تصمیم ۲۰۲۶-۱۰-۱۱): باقی‌مانده‌ی بسته‌های منقضی‌شده یک ردیف «expired» می‌گیرد
Schedule::command('loyalty:expire-points')
    ->dailyAt('03:20')
    ->name('loyalty-expire-points')
    ->withoutOverlapping()
    ->onOneServer();
