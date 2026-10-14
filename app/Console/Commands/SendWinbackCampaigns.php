<?php

namespace App\Console\Commands;

use App\Services\Marketing\WinbackCampaignService;
use Illuminate\Console\Command;

/** ارسال دسته‌ای کمپین‌های دستی «مشتری‌های قدیمی» (بسته‌ی ج) — هر دقیقه، فقط ۹ تا ۲۰ تهران */
class SendWinbackCampaigns extends Command
{
    protected $signature = 'winback:campaigns';

    protected $description = 'Send the next batch of manual win-back campaigns (09:00-20:00 Tehran)';

    public function handle(WinbackCampaignService $campaigns): int
    {
        $sent = $campaigns->runDue();

        $this->info(sprintf('%d پیامک کمپین برای %d کمپین فرستاده شد.', array_sum($sent), count($sent)));

        return self::SUCCESS;
    }
}
