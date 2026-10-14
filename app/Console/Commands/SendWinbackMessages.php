<?php

namespace App\Console\Commands;

use App\Services\Marketing\WinbackService;
use Illuminate\Console\Command;

/** پیامک روزانه‌ی «پیگیری مشتریانی که مدتی نیامده‌اند» برای همه‌ی سالن‌هایی که آن را روشن کرده‌اند (۲۰۲۶-۱۰-۱۴) */
class SendWinbackMessages extends Command
{
    protected $signature = 'customers:winback';

    protected $description = 'Text customers who have not been back for a while (per-salon win-back settings)';

    public function handle(WinbackService $winback): int
    {
        $sent = $winback->runAll();

        $this->info(sprintf('%d پیامک پیگیری برای %d سالن فرستاده شد.', array_sum($sent), count($sent)));

        return self::SUCCESS;
    }
}
