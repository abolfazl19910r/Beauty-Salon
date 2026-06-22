<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CancelUnpaidBookings;
use App\Console\Commands\SettlePendingWalletIncomes;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CancelUnpaidBookings())
    ->everyFiveMinutes()
    ->name('cancel-unpaid-bookings')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('wallet:settle-pending')
    ->dailyAt('01:00')
    ->name('wallet-settle-pending')
    ->withoutOverlapping()
    ->onOneServer();
