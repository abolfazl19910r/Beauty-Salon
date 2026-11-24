<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Support\JalaliDateInput;
use App\Support\SuperAdmin\SalonStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * ⭐ جستجو و فیلتر لیست سالن‌های سوپرادمین (۲۰۲۶-۰۹-۲۴) — روی همه‌ی ستون‌های همون صفحه: نام/آدرس
 * سالن، ادمین (مالک)، سقف/مصرف متخصص، «از تاریخ»، «اشتراک تا»، وضعیت؛ به‌علاوه‌ی پلن.
 */
class SalonListFilter
{
    /** دسته‌های جدا از هم (بسته‌ی ۱ ظاهری): App\Support\SuperAdmin\SalonStatus */
    public const STATUSES = SalonStatus::LABELS;

    public const DEFAULT_SORT = 'ends_asc';

    public const SORTS = [
        'ends_asc' => 'نزدیک‌ترین انقضا',
        'name' => 'نام سالن',
        'newest' => 'جدیدترین',
        'oldest' => 'قدیمی‌ترین',
        'ends_desc' => 'دورترین انقضا',
        'specialists_desc' => 'بیشترین کارشناس',
    ];

    public function query(array $filters): Builder
    {
        $query = Salon::query()->withCount('specialists')->withPaidInvoiceFlag()->with(['admins', 'plan']);

        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('salons.name', 'like', $like)
                    ->orWhere('salons.slug', 'like', $like)
                    ->orWhere('salons.phone', 'like', $like)
                    ->orWhere('salons.address', 'like', $like)
                    ->orWhereHas('admins', fn ($u) => $u->where('salon_admins.role', 'owner')
                        ->where(fn ($w) => $w->where('users.name', 'like', $like)->orWhere('users.phone', 'like', $like)));
            });
        }

        if (! empty($filters['status'])) {
            SalonStatus::scope($query, $filters['status']);
        }

        // پلن (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴)؛ «none» = سالن‌های بی‌پلن
        if (($filters['plan_id'] ?? null) === 'none') {
            $query->whereNull('plan_id');
        } elseif (! empty($filters['plan_id'])) {
            $query->where('plan_id', (int) $filters['plan_id']);
        }

        if (! empty($filters['subscription_type'])) {
            $query->where('subscription_type', $filters['subscription_type']);
        }

        match ($filters['quota'] ?? null) {
            'full' => $query->whereRaw('(select count(*) from specialists where specialists.salon_id = salons.id) >= salons.max_specialists_count'),
            'available' => $query->whereRaw('(select count(*) from specialists where specialists.salon_id = salons.id) < salons.max_specialists_count'),
            default => null,
        };

        foreach (['started' => 'subscription_started_at', 'ends' => 'subscription_ends_at'] as $prefix => $column) {
            if ($from = JalaliDateInput::toCarbon($filters["{$prefix}_from"] ?? null)) {
                $query->where($column, '>=', $from);
            }
            if ($to = JalaliDateInput::toCarbon($filters["{$prefix}_to"] ?? null, endOfDay: true)) {
                $query->where($column, '<=', $to);
            }
        }

        $now = now();
        match ($filters['sort'] ?? self::DEFAULT_SORT) {
            'newest' => $query->orderByDesc('salons.created_at')->orderByDesc('salons.id'),
            'oldest' => $query->orderBy('salons.created_at')->orderBy('salons.id'),
            // اول سالن‌های هنوز معتبر (نزدیک به دور)، بعد منقضی‌ها (تازه‌ترین منقضی اول) — مثل ترتیب زمانی نوبت‌ها
            'ends_asc' => $query
                ->orderByRaw('CASE WHEN salons.subscription_ends_at >= ? THEN 0 ELSE 1 END', [$now])
                ->orderByRaw('CASE WHEN salons.subscription_ends_at >= ? THEN salons.subscription_ends_at END ASC', [$now])
                ->orderByDesc('salons.subscription_ends_at')->orderBy('salons.id'),
            'ends_desc' => $query->orderByDesc('subscription_ends_at'),
            'specialists_desc' => $query->orderByDesc('specialists_count')->orderBy('salons.name'),
            default => $query->orderBy('salons.name'),
        };

        return $query;
    }

    /**
     * شمار هر دسته‌ی وضعیت با همان جستجو و «فیلترهای بیشتر» (بی خود فیلتر وضعیت) — برای تراشه‌ها؛ یک کوئری.
     *
     * @return array<string, int> کلیدهای SalonStatus::LABELS + total
     */
    public function statusCounts(array $filters): array
    {
        unset($filters['status'], $filters['sort']);
        [$sql, $bindings] = SalonStatus::caseSql();
        $ids = $this->query($filters)->reorder()->toBase()->cloneWithout(['columns'])->cloneWithoutBindings(['select'])->select('salons.id');

        $rows = Salon::query()->toBase()->whereIn('salons.id', $ids)
            ->selectRaw("{$sql} AS bucket, COUNT(*) AS n", $bindings)->groupBy('bucket')->pluck('n', 'bucket');

        $counts = array_map(fn () => 0, SalonStatus::LABELS);
        foreach ($rows as $bucket => $n) {
            $counts[$bucket] = (int) $n;
        }
        $counts['total'] = array_sum($counts);

        return $counts;
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }
}
