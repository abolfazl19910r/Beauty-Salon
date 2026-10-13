<?php

namespace App\Console\Commands;

use App\Models\LoyaltyPoint;
use App\Services\Loyalty\LoyaltyLedger;
use Illuminate\Console\Command;

/**
 * ثبت انقضای امتیاز باشگاه مشتریان (تصمیم ۲۰۲۶-۱۰-۱۱، گزینه‌ی «الف»): برای هر مشتری که بسته‌ی امتیاز منقضی‌شده دارد،
 * باقی‌مانده‌ی خرج‌نشده‌ی آن بسته‌ها با یک ردیف «expired» کم می‌شود. روزانه در scheduler؛ دوباره اجرا شدن بی‌اثر است.
 * loyalty_points ستون salon_id ندارد (امتیاز مال مشتری است)، پس بدون سالن جاری کار می‌کند.
 */
class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'ثبت انقضای امتیازهای خرج‌نشده‌ی باشگاه مشتریان';

    public function handle(LoyaltyLedger $ledger): int
    {
        $users = 0;
        $points = 0;

        LoyaltyPoint::query()
            ->where('points', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->each(function (int $userId) use ($ledger, &$users, &$points) {
                $expired = $ledger->expire($userId);
                if ($expired > 0) {
                    $users++;
                    $points += $expired;
                }
            });

        $this->info("امتیاز منقضی‌شده: {$points} (مشتری: {$users})");

        return self::SUCCESS;
    }
}
