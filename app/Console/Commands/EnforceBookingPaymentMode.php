<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingModeGuard;
use Illuminate\Console\Command;

/**
 * بسته‌ی 5ه، تصمیم ۲: سالن «با پیش‌پرداخت» که درگاه فعال یا امکان پرداخت آنلاین پلن را از دست داده خودکار «بدون پیش‌پرداخت» می‌شود و
 * مالکش هشدار می‌گیرد (تغییر پلن، پایان اشتراک؛ تغییر درگاه همان لحظه از observer بررسی می‌شود).
 */
class EnforceBookingPaymentMode extends Command
{
    protected $signature = 'bookings:enforce-payment-mode';

    protected $description = 'سالن‌های «با پیش‌پرداخت» بی درگاه/امکان پلن را به «بدون پیش‌پرداخت» برمی‌گرداند';

    public function handle(BookingModeGuard $guard): int
    {
        $count = $guard->enforceAll();
        $this->info("Salons switched to no-prepayment: {$count}");

        return self::SUCCESS;
    }
}
