<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Support\JalaliDateInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * ⭐ جستجو و فیلتر لیست سالن‌های سوپرادمین (۲۰۲۶-۰۹-۲۴) — روی همه‌ی ستون‌های همون صفحه: نام/آدرس
 * سالن، ادمین (مالک)، سقف/مصرف متخصص، «از تاریخ»، «اشتراک تا»، وضعیت؛ به‌علاوه‌ی پلن.
 */
class SalonListFilter
{
    public const STATUSES = [
        'active' => 'فعال (همه)',
        'trial' => 'در دوره‌ی آزمایشی',
        'expiring_soon' => 'رو به انقضا (۷ روز)',
        'expired' => 'منقضی',
        'suspended' => 'تعلیق‌شده',
    ];

    public const SORTS = [
        'name' => 'نام سالن',
        'newest' => 'جدیدترین',
        'oldest' => 'قدیمی‌ترین',
        'ends_asc' => 'نزدیک‌ترین انقضا',
        'ends_desc' => 'دورترین انقضا',
        'specialists_desc' => 'بیشترین متخصص',
    ];

    public function query(array $filters): Builder
    {
        $query = Salon::query()->withCount('specialists')->with('admins');

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

        $now = now();
        match ($filters['status'] ?? null) {
            'suspended' => $query->where('is_suspended', true),
            'expired' => $query->where('is_suspended', false)->where('subscription_ends_at', '<', $now),
            'active' => $query->where('is_suspended', false)->where('subscription_ends_at', '>=', $now),
            'expiring_soon' => $query->where('is_suspended', false)
                ->whereBetween('subscription_ends_at', [$now, $now->copy()->addDays(7)]),
            'trial' => $query->where('is_suspended', false)
                ->where('subscription_ends_at', '>=', $now)
                ->where('trial_ends_at', '>', $now)
                ->whereDoesntHave('invoices', fn ($i) => $i->withoutGlobalScope('salon')->where('status', 'paid')),
            default => null,
        };

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

        match ($filters['sort'] ?? 'name') {
            'newest' => $query->orderByDesc('salons.created_at')->orderByDesc('salons.id'),
            'oldest' => $query->orderBy('salons.created_at')->orderBy('salons.id'),
            'ends_asc' => $query->orderBy('subscription_ends_at'),
            'ends_desc' => $query->orderByDesc('subscription_ends_at'),
            'specialists_desc' => $query->orderByDesc('specialists_count')->orderBy('salons.name'),
            default => $query->orderBy('salons.name'),
        };

        return $query;
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }
}
