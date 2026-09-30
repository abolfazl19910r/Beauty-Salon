<?php

namespace App\Repositories\Eloquent;

use App\Models\Salon;
use App\Repositories\Contracts\SalonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalonRepository extends BaseRepository implements SalonRepositoryInterface
{
    public function __construct(Salon $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Salon
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function slugExists(string $slug): bool
    {
        return $this->model->where('slug', $slug)->exists();
    }

    public function lockForUpdateFindOrFail(int|string $id): Salon
    {
        return $this->model->lockForUpdate()->findOrFail($id);
    }

    public function getOldestSlug(): ?string
    {
        return $this->model->query()->oldest('id')->value('slug');
    }

    /**
     * شمارنده‌های داشبورد سوپرادمین در یک کوئری (۲۰۲۶-۰۹-۳۰: قبلاً همه‌ی سالن‌ها با مدیرانشان بار و در PHP شمرده می‌شدند).
     * تعریف‌ها همان Salon::hasActiveSubscription() است: فعال = تعلیق‌نشده و subscription_ends_at در آینده؛ منقضی = subscription_ends_at
     * در گذشته (معلق یا نه)؛ رو به انقضا = فعال و حداکثر ۷ روز مانده.
     *
     * @return array{active_salons: int, expiring_soon: int, expired: int}
     */
    public function subscriptionCounts(\DateTimeInterface $now): array
    {
        $soon = \Illuminate\Support\Carbon::instance($now)->addDays(7);
        $row = $this->model->newQuery()->toBase()
            ->selectRaw('SUM(CASE WHEN is_suspended = ? AND subscription_ends_at > ? THEN 1 ELSE 0 END) AS active_salons', [false, $now])
            ->selectRaw('SUM(CASE WHEN is_suspended = ? AND subscription_ends_at > ? AND subscription_ends_at <= ? THEN 1 ELSE 0 END) AS expiring_soon', [false, $now, $soon])
            ->selectRaw('SUM(CASE WHEN subscription_ends_at < ? THEN 1 ELSE 0 END) AS expired', [$now])
            ->first();

        return [
            'active_salons' => (int) $row->active_salons,
            'expiring_soon' => (int) $row->expiring_soon,
            'expired' => (int) $row->expired,
        ];
    }

    public function getRecentWithSpecialistCount(int $limit = 5): Collection
    {
        return $this->model->withCount('specialists')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function paginateWithSpecialistCountAndAdmins(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->withCount('specialists')
            ->with('admins')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
