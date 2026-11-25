<?php

namespace App\Services\Customer;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * «نوبت‌های من» مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): تب «پیش رو / گذشته / لغوشده» جای فیلتر وضعیت و تاریخ.
 * - پیش رو: از امروز به بعد و هنوز باز (منتظر پرداخت / منتظر تأیید / تأییدشده)، نزدیک به دور. نشان شمار تب «نوبت‌های من»
 *   در سربرگ هم از همین تعریف است تا دو عدد یکی باشند.
 * - گذشته: لغونشده‌ای که بسته شده (انجام‌شده/نیامد) یا روزش گذشته، تازه به قدیم.
 * - لغوشده: تازه به قدیم.
 */
final class CustomerBookingListFilter
{
    public const TABS = [
        'upcoming' => 'پیش رو',
        'past' => 'گذشته',
        'cancelled' => 'لغوشده',
    ];

    public const DEFAULT_TAB = 'upcoming';

    public const OPEN_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    public static function forUser(int $userId): Builder
    {
        return Booking::query()->where('user_id', $userId);
    }

    public static function applyTab(Builder $query, string $tab): Builder
    {
        $today = now()->startOfDay();

        return match ($tab) {
            'past' => $query->where('status', '!=', 'cancelled')
                ->where(fn ($q) => $q->whereIn('status', ['completed', 'no_show'])->orWhere('booking_time', '<', $today)),
            'cancelled' => $query->where('status', 'cancelled'),
            default => $query->whereIn('status', self::OPEN_STATUSES)->where('booking_time', '>=', $today),
        };
    }

    public static function upcomingCount(int $userId): int
    {
        return self::applyTab(self::forUser($userId), 'upcoming')->count();
    }

    /** @return array<string, int> */
    public static function tabCounts(int $userId): array
    {
        $counts = [];
        foreach (array_keys(self::TABS) as $tab) {
            $counts[$tab] = self::applyTab(self::forUser($userId), $tab)->count();
        }

        return $counts;
    }

    public static function paginate(int $userId, string $tab, int $perPage = 10): LengthAwarePaginator
    {
        $query = self::applyTab(self::forUser($userId), $tab)->with(['service', 'specialist']);

        $tab === 'upcoming'
            ? $query->orderBy('booking_time')->orderBy('id')
            : $query->orderByDesc('booking_time')->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }
}
