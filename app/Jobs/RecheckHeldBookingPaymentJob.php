<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Services\Payment\HeldBookingPayment;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * اولین پرسش دوباره‌ی پرداخت نگه‌داشته (تصمیم ۴-۳ الف): یک دقیقه بعد از برگشت مشتری؛ بعد از آن payments:reconcile هر ۵ دقیقه.
 */
class RecheckHeldBookingPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $transactionId)
    {
        $this->onQueue(Queues::PAYMENTS);
    }

    public function handle(HeldBookingPayment $held): void
    {
        $tx = PaymentTransaction::find($this->transactionId);
        if ($tx && HeldBookingPayment::isHeld($tx)) {
            $held->check($tx);
        }
    }
}
