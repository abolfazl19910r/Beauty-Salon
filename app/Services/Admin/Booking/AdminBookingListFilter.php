<?php

namespace App\Services\Admin\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * فهرست نوبت‌های پنل مدیر (بسته‌ی ۲ ظاهری، ۲۰۲۶-۱۱-۲۴): تب امروز / فردا / این هفته / همه (پیش‌فرض امروز؛ «این هفته» = شنبه
 * تا جمعه‌ی همین هفته)، جستجوی نام یا موبایل مشتری، فیلتر وضعیت و کارشناس. ترتیب همان chronological بسته‌ی ۰.
 */
final class AdminBookingListFilter
{
    public const TABS = [
        'today' => 'امروز',
        'tomorrow' => 'فردا',
        'week' => 'این هفته',
        'all' => 'همه',
    ];

    public const DEFAULT_TAB = 'today';

    /** @return array{0: Carbon, 1: Carbon}|null */
    public static function range(string $tab, ?Carbon $now = null): ?array
    {
        $now ??= now();

        return match ($tab) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'tomorrow' => [$now->copy()->addDay()->startOfDay(), $now->copy()->addDay()->endOfDay()],
            'week' => [
                Carbon::instance(verta($now)->startWeek()->datetime())->startOfDay(),
                Carbon::instance(verta($now)->endWeek()->datetime())->endOfDay(),
            ],
            default => null,
        };
    }

    /** جستجو، وضعیت و کارشناس (بی تب) */
    public function base(array $filters): Builder
    {
        $query = Booking::query();

        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $digits = \App\Http\Controllers\SuperAdmin\PhoneSearchController::normalize($term);
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $query->whereHas('user', function (Builder $u) use ($like, $digits) {
                $u->where('name', 'like', $like);
                if (strlen($digits) >= 4) {
                    $u->orWhere('phone', 'like', '%'.$digits.'%');
                }
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['specialist_id'])) {
            $query->where('specialist_id', (int) $filters['specialist_id']);
        }

        return $query;
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->base($filters)->with(['user', 'specialist', 'service'])->chronological();
        if ($range = self::range($filters['tab'] ?? self::DEFAULT_TAB)) {
            $query->whereBetween('booking_time', $range);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * شمار هر تب با همان جستجو و فیلترها.
     *
     * @return array<string, int>
     */
    public function tabCounts(array $filters): array
    {
        $counts = [];
        foreach (array_keys(self::TABS) as $tab) {
            $query = $this->base($filters);
            if ($range = self::range($tab)) {
                $query->whereBetween('booking_time', $range);
            }
            $counts[$tab] = $query->count();
        }

        return $counts;
    }

    /** نوبت‌های آینده‌ی منتظر کار مدیر (تأیید یا پرداخت) — همان شمار منو */
    public function waitingCount(): int
    {
        return Booking::query()->whereIn('status', ['pending', 'pending_payment'])->where('booking_time', '>=', now())->count();
    }
}
