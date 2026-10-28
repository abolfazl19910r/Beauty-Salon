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

// بسته‌ی 5ه: «با پیش‌پرداخت» بی درگاه فعال یا بی امکان پرداخت آنلاین پلن ← «بدون پیش‌پرداخت» + هشدار به مالک
Schedule::command('bookings:enforce-payment-mode')
    ->everyTenMinutes()
    ->name('enforce-booking-payment-mode')
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

// لوگوی ثبت‌نام‌های سالنِ تأییدنشده (SalonSignupChallenge) بعد از یک روز پاک می‌شود — challenge خودش ۱۰ دقیقه عمر دارد
Schedule::call(function () {
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    foreach ($disk->files(\App\Services\SalonSignup\SalonSignupChallenge::PENDING_LOGO_DIR) as $file) {
        if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
            $disk->delete($file);
        }
    }
})->dailyAt('03:40')->name('salon-signup-pending-logos')->onOneServer();
