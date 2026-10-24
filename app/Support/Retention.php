<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * مهلت‌های نگهداری داده (config/retention.php). هر مقدار کمتر از حداقل قانونی (ماده‌ی ۶۶۸ آیین دادرسی کیفری) به همان حداقل
 * برگردانده می‌شود تا یک اشتباه در .env داده را زودتر از قانون پاک نکند.
 */
class Retention
{
    public static function days(string $key): int
    {
        $value = config("retention.{$key}");
        $days = is_numeric($value) ? (int) $value : (int) config('retention.days', 190);

        return max($days, (int) config('retention.minimum_days', 184));
    }

    public static function cutoff(string $key): Carbon
    {
        return now()->subDays(self::days($key));
    }
}
