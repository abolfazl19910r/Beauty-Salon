<?php

namespace App\Console\Commands;

use App\Services\Plans\DefaultPlans;
use Illuminate\Console\Command;

/**
 * ساخت پلن‌های پیش‌فرض (اگر نیستند) و وصل کردن سالن‌های بی‌پلن: در آزمایشی ← «آزمایشی»، بقیه ← «کامل» — بدون هیچ تغییر رفتاری.
 * چند بار اجرا بی‌خطر است. DatabaseSeeder هم صدایش می‌زند.
 */
class SyncDefaultPlans extends Command
{
    protected $signature = 'plans:sync-defaults';

    protected $description = 'Create the default subscription plans if missing and attach salons that have no plan';

    public function handle(DefaultPlans $plans): int
    {
        $result = $plans->sync();

        $this->info(sprintf(
            'پلن‌های تازه: %s — سالن‌های وصل‌شده: %d به «آزمایشی»، %d به «کامل».',
            $result['created'] === [] ? 'هیچ' : implode('، ', $result['created']),
            $result['assigned_trial'],
            $result['assigned_full'],
        ));

        if ($result['trial_specialists_updated']) {
            $this->info(sprintf('سقف کارشناس پلن «آزمایشی» از %d به %d رسید.', DefaultPlans::LEGACY_TRIAL_SPECIALISTS, (int) config('billing.trial_specialists', 2)));
        }

        if ($result['descriptions_updated'] !== []) {
            $this->info('توضیح کوتاه پلن‌ها (کارت لندینگ) به‌روز شد: '.implode('، ', $result['descriptions_updated']).'.');
        }

        return self::SUCCESS;
    }
}
