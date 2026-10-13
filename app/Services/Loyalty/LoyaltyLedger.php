<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * موجودی امتیاز باشگاه مشتریان با انقضا (تصمیم ۲۰۲۶-۱۰-۱۱، گزینه‌ی «الف») — تنها منبع حساب امتیاز.
 *
 * هر ردیف مثبت (earned) یک «بسته‌ی امتیاز» با تاریخ انقضای خودش است؛ ردیف‌های منفی (spent، expired) مصرف‌اند و به ترتیب
 * از زودتر منقضی‌شونده‌ها کم می‌شوند (FIFO بر اساس expires_at، بعد شناسه؛ بی‌تاریخ آخر). موجودی = باقی‌مانده‌ی بسته‌هایی که
 * هنوز منقضی نشده‌اند. قبلاً امتیاز منقضی‌شده هم خرج می‌شد: هیچ‌چیز انقضا را ثبت نمی‌کرد و بیشتر جاها کل ردیف‌ها را جمع می‌زدند.
 *
 * expire() باقی‌مانده‌ی بسته‌های منقضی‌شده را با یک ردیف «expired» ثبت می‌کند تا تاریخچه‌ی مشتری دلیل کم شدن را نشان دهد
 * (فرمان روزانه‌ی loyalty:expire-points و پیش از هر دریافت پاداش). موجودی حتی پیش از اجرای فرمان درست است.
 */
class LoyaltyLedger
{
    public const TYPE_EXPIRED = 'expired';

    /** بسته‌های مثبت با باقی‌مانده‌ی هر کدام (ویژگی remaining). */
    public function lots(int $userId): Collection
    {
        $rows = LoyaltyPoint::query()->where('user_id', $userId)->orderBy('id')->get(['id', 'points', 'type', 'description', 'expires_at']);

        $consumed = (int) abs($rows->where('points', '<', 0)->sum('points'));

        $lots = $rows->where('points', '>', 0)->sort(function (LoyaltyPoint $a, LoyaltyPoint $b) {
            $ea = $a->expires_at?->getTimestamp() ?? PHP_INT_MAX;
            $eb = $b->expires_at?->getTimestamp() ?? PHP_INT_MAX;

            return [$ea, $a->id] <=> [$eb, $b->id];
        })->values();

        foreach ($lots as $lot) {
            $take = min($consumed, (int) $lot->points);
            $lot->remaining = (int) $lot->points - $take;
            $consumed -= $take;
        }

        return $lots;
    }

    public function balance(int $userId): int
    {
        return (int) $this->lots($userId)
            ->filter(fn (LoyaltyPoint $lot) => ! $this->isExpired($lot))
            ->sum('remaining');
    }

    /** امتیازی که تا $days روز آینده منقضی می‌شود و هنوز خرج نشده است. */
    public function expiringSoon(int $userId, int $days = 30): int
    {
        $until = now()->addDays($days);

        return (int) $this->lots($userId)
            ->filter(fn (LoyaltyPoint $lot) => $lot->expires_at && $lot->expires_at->isFuture() && $lot->expires_at->lte($until))
            ->sum('remaining');
    }

    /**
     * باقی‌مانده‌ی بسته‌های منقضی‌شده را ثبت می‌کند (idempotent: بعد از ثبت، باقی‌مانده‌ی آن بسته‌ها صفر است).
     *
     * @return int مجموع امتیاز منقضی‌شده‌ی تازه
     */
    public function expire(int $userId): int
    {
        $expired = DB::transaction(function () use ($userId) {
            User::query()->whereKey($userId)->lockForUpdate()->first();

            $total = 0;
            foreach ($this->lots($userId) as $lot) {
                if ($lot->remaining > 0 && $this->isExpired($lot)) {
                    LoyaltyPoint::create([
                        'user_id' => $userId,
                        'points' => -$lot->remaining,
                        'type' => self::TYPE_EXPIRED,
                        'description' => 'انقضای امتیاز — '.$lot->description,
                    ]);
                    $total += $lot->remaining;
                }
            }

            return $total;
        });

        if ($expired > 0) {
            Cache::forget("user:{$userId}:loyalty_points");
        }

        return $expired;
    }

    private function isExpired(LoyaltyPoint $lot): bool
    {
        return $lot->expires_at !== null && ! $lot->expires_at->isFuture();
    }
}
