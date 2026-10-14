<?php

namespace App\Console\Commands;

use App\Models\Salon;
use Illuminate\Console\Command;

/**
 * پلن بعدیِ زمان‌بندی‌شده (خرید پلن دیگر وسط اشتراک فعال) را وقتی دوره‌اش شروع شد پلن اصلی سالن می‌کند. محاسبه‌ی امکانات و قیمت
 * بدون این دستور هم درست است (Salon::effectivePlanId)؛ این فقط داده را تمیز نگه می‌دارد تا صفحه‌ها و گزارش‌ها plan_id درست ببینند.
 */
class ApplyScheduledPlans extends Command
{
    protected $signature = 'plans:apply-scheduled';

    protected $description = 'Switch salons to their scheduled next plan once its period has started';

    public function handle(): int
    {
        $count = 0;

        Salon::withoutGlobalScopes()->whereNotNull('next_plan_id')->where('next_plan_starts_at', '<=', now())
            ->orderBy('id')
            ->each(function (Salon $salon) use (&$count) {
                $salon->update(['plan_id' => $salon->next_plan_id, 'next_plan_id' => null, 'next_plan_starts_at' => null]);
                $count++;
            });

        $this->info("{$count} سالن به پلن بعدی‌شان رفتند.");

        return self::SUCCESS;
    }
}
