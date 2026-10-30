<?php

namespace App\Console\Commands;

use App\Services\Payment\InvoiceInstallments;
use Illuminate\Console\Command;

/** فاکتورهای نیمه‌پرداختی که مهلت ۷ روزه‌شان گذشت ← اعتبار پلتفرم سالن (تصمیم ۲۰۲۶-۱۰-۳۱) */
class ExpireInvoiceInstallments extends Command
{
    protected $signature = 'billing:expire-installments';

    protected $description = 'بستن فاکتورهای نیمه‌پرداخت بعد از ۷ روز و افزودن قسط‌های پرداخت‌شده به اعتبار پلتفرم سالن';

    public function handle(InvoiceInstallments $installments): int
    {
        $this->info('فاکتورهای بسته‌شده: '.$installments->expire());

        return self::SUCCESS;
    }
}
