<?php

namespace App\Services\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use App\Support\Fa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * نوبت‌های پنل کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم «الف»): تب «پیش رو / منتظر تأیید / گذشته / لغوشده» + یک جستجو
 * (نام یا موبایل مشتری)، جای فرم فیلتر تاریخ/ساعت/پرداخت.
 * - پیش رو: از امروز به بعد و هنوز باز (منتظر پرداخت / منتظر تأیید / تأییدشده)، نزدیک به دور — نوبت امروزی که ساعتش گذشته هم
 *   همین‌جاست تا «انجام شد» یا «نیامد» بخورد.
 * - منتظر تأیید: نوبت آینده‌ی pending؛ نشان شمار منو هم از همین تعریف است (waiting) تا دو عدد یکی باشند.
 * - گذشته: لغونشده‌ای که بسته شده (انجام‌شده/نیامد) یا روزش گذشته، تازه به قدیم.
 * - لغوشده: تازه به قدیم.
 */
final class SpecialistBookingListFilter
{
    public const TABS = [
        'upcoming' => 'پیش رو',
        'pending' => 'منتظر تأیید',
        'past' => 'گذشته',
        'cancelled' => 'لغوشده',
    ];

    public const DEFAULT_TAB = 'upcoming';

    public const OPEN_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    public static function forSpecialist(Specialist $specialist): Builder
    {
        return Booking::query()->where('specialist_id', $specialist->id);
    }

    public static function waiting(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('booking_time', '>=', now());
    }

    public static function waitingCount(Specialist $specialist): int
    {
        return self::waiting(self::forSpecialist($specialist))->count();
    }

    public static function applyTab(Builder $query, string $tab): Builder
    {
        $today = now()->startOfDay();

        return match ($tab) {
            'pending' => self::waiting($query),
            'past' => $query->where('status', '!=', 'cancelled')
                ->where(fn ($q) => $q->whereIn('status', ['completed', 'no_show'])->orWhere('booking_time', '<', $today)),
            'cancelled' => $query->where('status', 'cancelled'),
            default => $query->whereIn('status', self::OPEN_STATUSES)->where('booking_time', '>=', $today),
        };
    }

    public static function applySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $latin = Fa::latin($search);

        return $query->whereHas('user', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$search.'%')
            ->orWhere('phone', 'like', '%'.$latin.'%')));
    }

    /** @return array<string, int> */
    public static function tabCounts(Specialist $specialist, ?string $search = null): array
    {
        $counts = [];
        foreach (array_keys(self::TABS) as $tab) {
            $counts[$tab] = self::applyTab(self::applySearch(self::forSpecialist($specialist), $search), $tab)->count();
        }

        return $counts;
    }

    public static function paginate(Specialist $specialist, string $tab, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $query = self::applyTab(self::applySearch(self::forSpecialist($specialist), $search), $tab)
            ->with(['service', 'user']);

        in_array($tab, ['upcoming', 'pending'], true)
            ? $query->orderBy('booking_time')->orderBy('id')
            : $query->orderByDesc('booking_time')->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }
}
