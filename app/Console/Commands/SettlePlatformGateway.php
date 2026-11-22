<?php

namespace App\Console\Commands;

use App\Services\Payment\PlatformGateway\PlatformGatewayLedger;
use Illuminate\Console\Command;

/**
 * تسویه‌ی خودکار درگاه پلتفرم (۲۰۲۶-۱۱-۲۲): برای هر سالن که مانده‌ی تسویه‌نشده‌اش دست‌کم حداقل است یک تسویه‌ی «منتظر واریز»
 * می‌سازد (همان «ساخت تسویه»ی پنل ابرمدیر). واریز بانکی را نمی‌فرستد: زرین‌پال در مستندات رسمی API واریز به شخص ثالث ندارد؛
 * ابرمدیر از «درگاه پلتفرم» ← خروجی CSV یا هر سالن، واریز می‌کند و شماره‌ی پیگیری را ثبت می‌کند.
 * تراکنش‌های ۲۴ ساعت اخیر کنار می‌مانند تا برگشت‌های تازه (reversing) پیش از تسویه روشن شوند.
 */
class SettlePlatformGateway extends Command
{
    protected $signature = 'platform-gateway:settle {--min-toman= : حداقل مانده (تومان)؛ پیش‌فرض config} {--dry-run : فقط گزارش}';

    protected $description = 'Create pending settlements for salons using the platform payment gateway';

    public function handle(PlatformGatewayLedger $ledger): int
    {
        $minToman = $this->option('min-toman') !== null ? (int) $this->option('min-toman') : (int) config('payments.platform_gateway.auto_settle_min_toman', 100000);
        $cutoff = now()->subDay();

        if ($this->option('dry-run')) {
            $due = collect($ledger->summaries())->filter(fn (array $s) => $s['outstanding'] >= max(1, $minToman * 10));
            foreach ($due as $salonId => $s) {
                $this->line(sprintf('سالن %d: %s تومان (%d تراکنش)', $salonId, number_format(intdiv($s['outstanding'], 10)), $s['unsettled_count']));
            }
            $this->info('[dry-run] سالن‌های دارای مانده: '.$due->count());

            return self::SUCCESS;
        }

        $created = $ledger->createDue($minToman * 10, $cutoff);
        $this->info(sprintf('تسویه‌های ساخته‌شده: %d (جمع %s تومان)', count($created),
            number_format(intdiv(array_sum(array_map(fn ($s) => $s->salon_share_rial, $created)), 10))));

        return self::SUCCESS;
    }
}
