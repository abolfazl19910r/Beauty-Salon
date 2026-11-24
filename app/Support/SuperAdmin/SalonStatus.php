<?php

namespace App\Support\SuperAdmin;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * وضعیت اشتراک سالن در پنل ابرمدیر (بسته‌ی ۱ به‌روزرسانی ظاهری، ۲۰۲۶-۱۱-۲۴) — یک منبع برای تراشه‌های فهرست سالن‌ها،
 * نوار وضعیت داشبورد و برچسب هر ردیف.
 *
 * دسته‌ها جدا از هم‌اند (تصمیم ابوالفضل): جمع شمارشان همیشه برابر کل سالن‌هاست. به ترتیب اولویت:
 * تعلیق‌شده ← منقضی (یا بی‌تاریخ) ← آزمایشی (دوره‌ی آزمایشی باز و بی فاکتور پرداخت‌شده) ← نزدیک انقضا (۷ روز) ← فعال.
 * همین ترتیب هم در SQL (caseSql) و هم در PHP (of) پیاده است تا شمار تراشه و برچسب ردیف هرگز از هم جدا نشوند.
 */
final class SalonStatus
{
    public const EXPIRING_DAYS = 7;

    /** @var array<string, string> */
    public const LABELS = [
        'active' => 'فعال',
        'trial' => 'آزمایشی',
        'expiring_soon' => 'نزدیک انقضا',
        'expired' => 'منقضی',
        'suspended' => 'تعلیق‌شده',
    ];

    /** رنگ (کلید توکن --sa-*) هر دسته */
    public const TONES = [
        'active' => 'success',
        'trial' => 'accent',
        'expiring_soon' => 'warn',
        'expired' => 'danger',
        'suspended' => 'danger',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * عبارت CASE که دسته‌ی هر ردیف salons را برمی‌گرداند.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public static function caseSql(?Carbon $now = null): array
    {
        $now ??= now();
        $soon = $now->copy()->addDays(self::EXPIRING_DAYS);

        $sql = 'CASE'
            .' WHEN salons.is_suspended = ? THEN \'suspended\''
            .' WHEN salons.subscription_ends_at IS NULL OR salons.subscription_ends_at < ? THEN \'expired\''
            .' WHEN salons.trial_ends_at IS NOT NULL AND salons.trial_ends_at > ?'
            .' AND NOT EXISTS (SELECT 1 FROM invoices WHERE invoices.salon_id = salons.id AND invoices.status = \'paid\') THEN \'trial\''
            .' WHEN salons.subscription_ends_at <= ? THEN \'expiring_soon\''
            .' ELSE \'active\' END';

        return [$sql, [true, $now, $now, $soon]];
    }

    /** فقط سالن‌های یک دسته */
    public static function scope(Builder $query, string $status, ?Carbon $now = null): Builder
    {
        [$sql, $bindings] = self::caseSql($now);

        return $query->whereRaw("({$sql}) = ?", [...$bindings, $status]);
    }

    /**
     * شمار هر دسته با یک کوئری.
     *
     * @return array<string, int> همه‌ی کلیدهای LABELS + total
     */
    public static function counts(?Carbon $now = null): array
    {
        [$sql, $bindings] = self::caseSql($now);
        $rows = DB::table('salons')->selectRaw("{$sql} AS bucket, COUNT(*) AS n", $bindings)
            ->groupBy('bucket')->pluck('n', 'bucket');

        $counts = array_map(fn () => 0, self::LABELS);
        foreach ($rows as $bucket => $n) {
            $counts[$bucket] = (int) $n;
        }
        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /** دسته‌ی یک سالن (همان قاعده‌ی caseSql) */
    public static function of(Salon $salon, ?Carbon $now = null): string
    {
        $now ??= now();
        $ends = $salon->subscription_ends_at;

        return match (true) {
            (bool) $salon->is_suspended => 'suspended',
            $ends === null || $ends->lt($now) => 'expired',
            $salon->trial_ends_at !== null && $salon->trial_ends_at->gt($now) && ! $salon->hasPaidInvoice() => 'trial',
            $ends->lte($now->copy()->addDays(self::EXPIRING_DAYS)) => 'expiring_soon',
            default => 'active',
        };
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function tone(string $status): string
    {
        return self::TONES[$status] ?? 'accent';
    }

    /**
     * «۷ روز مانده» / «۹ ماه مانده» / «امروز تمام می‌شود» / «۳ روز پیش تمام شد».
     *
     * @return array{text: string, urgent: bool}|null
     */
    public static function remaining(?Carbon $ends, ?Carbon $now = null): ?array
    {
        if ($ends === null) {
            return null;
        }
        $now ??= now();

        if ($ends->lt($now)) {
            $days = (int) floor($ends->diffInSeconds($now) / 86400);

            return ['text' => $days < 1 ? 'امروز تمام شد' : fa_num($days).' روز پیش تمام شد', 'urgent' => true];
        }

        $days = (int) ceil($now->diffInSeconds($ends) / 86400);
        if ($days <= 1 && $ends->isSameDay($now)) {
            return ['text' => 'امروز تمام می‌شود', 'urgent' => true];
        }
        if ($days <= 60) {
            return ['text' => fa_num($days).' روز مانده', 'urgent' => $days <= self::EXPIRING_DAYS];
        }

        return ['text' => fa_num((int) floor($now->diffInMonths($ends, true))).' ماه مانده', 'urgent' => false];
    }
}
