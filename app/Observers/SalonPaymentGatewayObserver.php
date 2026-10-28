<?php

namespace App\Observers;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Services\Booking\BookingModeGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * درگاه سالن غیرفعال یا حذف شد (بسته‌ی 5ه، تصمیم ۲): اگر دیگر درگاه فعالی نماند و سالن «با پیش‌پرداخت» بود، همان لحظه به «بدون پیش‌پرداخت»
 * برمی‌گردد و مالک هشدار می‌گیرد (BookingModeGuard). بعد از commit، تا تغییر درگاه قطعی شده باشد.
 */
class SalonPaymentGatewayObserver
{
    public function updated(SalonPaymentGateway $gateway): void
    {
        if ($gateway->wasChanged('is_active') && ! $gateway->is_active) {
            $this->check($gateway);
        }
    }

    public function deleted(SalonPaymentGateway $gateway): void
    {
        $this->check($gateway);
    }

    private function check(SalonPaymentGateway $gateway): void
    {
        $salonId = (int) $gateway->salon_id;

        DB::afterCommit(function () use ($salonId) {
            try {
                if ($salon = Salon::find($salonId)) {
                    app(BookingModeGuard::class)->enforce($salon);
                }
            } catch (\Throwable $e) {
                Log::error('Booking payment mode check after gateway change failed', ['salon_id' => $salonId, 'error' => $e->getMessage()]);
            }
        });
    }
}
