<?php

namespace App\Console\Commands;

use App\Models\SalonPaymentGateway;
use App\Payments\Drivers\VandarPayoutDriver;
use Illuminate\Console\Command;

/**
 * ⭐ توکن API وندار پنج روز اعتبار داره و هر تمدید refresh_token جدید می‌ده. سالنی که چند روز تسویه نداشته باشه
 * بدون این کار روزانه توکنش منقضی می‌شد؛ حالا هر روز تمدید و هر دو توکن تازه ذخیره می‌شن (VandarPayoutDriver).
 */
class RefreshVandarPayoutTokens extends Command
{
    protected $signature = 'payouts:refresh-vandar-tokens';

    protected $description = 'تمدید روزانه‌ی توکن‌های تسویه‌ی وندار همه‌ی سالن‌ها';

    public function handle(): int
    {
        $refreshed = 0;
        $failed = 0;

        SalonPaymentGateway::query()->where('driver', 'vandar')->orderBy('id')->each(function (SalonPaymentGateway $gateway) use (&$refreshed, &$failed) {
            if (! VandarPayoutDriver::isConfigured((array) $gateway->credentials)) {
                return;
            }

            (new VandarPayoutDriver((array) $gateway->credentials, $gateway))->refreshTokens() ? $refreshed++ : $failed++;
        });

        $this->info("توکن وندار: {$refreshed} تمدید شد، {$failed} ناموفق");

        return self::SUCCESS;
    }
}
