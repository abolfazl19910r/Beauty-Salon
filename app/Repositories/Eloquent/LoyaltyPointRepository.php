<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoyaltyPointRepository extends BaseRepository implements LoyaltyPointRepositoryInterface
{
    public function __construct(LoyaltyPoint $model)
    {
        parent::__construct($model);
    }

    public function sumForUser(int $userId): int
    {
        return (int) $this->model->where('user_id', $userId)->sum('points');
    }

    public function sumExpiringForUser(int $userId, int $days): int
    {
        return (int) $this->model->where('user_id', $userId)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->sum('points');
    }

    public function sumExpiringSoonForUser(int $userId, int $days): int
    {
        return (int) $this->model->where('user_id', $userId)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->sum('points');
    }

    public function sumForUserByType(int $userId, string $type): int
    {
        return (int) $this->model->where('user_id', $userId)
            ->where('type', $type)
            ->sum('points');
    }

    public function sumByType(string $type, ?int $salonId = null): int
    {
        return (int) $this->forSalon($salonId)->where('type', $type)->sum('points');
    }

    public function countDistinctUsers(?int $salonId = null): int
    {
        return $this->forSalon($salonId)->distinct('user_id')->count('user_id');
    }

    /**
     * loyalty_points ستون salon_id نداره؛ امتیاز همیشه مال یک مشتریه و مشتری salon_id داره.
     */
    private function forSalon(?int $salonId): Builder
    {
        return $this->model->newQuery()->when($salonId !== null, fn (Builder $q) => $q->whereIn(
            'user_id',
            User::query()->select('id')->where('user_type', 'customer')->where('salon_id', $salonId)
        ));
    }

    public function countByType(string $type): int
    {
        return $this->model->where('type', $type)->count();
    }

    public function paginateForUserWithBooking(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->with(['booking' => function ($query) {
                $query->select('id', 'booking_time', 'service_id', 'specialist_id')
                    ->with(['service:id,name', 'specialist:id,name']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with('user:id,name,phone')
            ->orderByDesc('created_at');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($filters['to'])->endOfDay());
        }

        return $query->paginate($perPage);
    }

    public function topUsersByPoints(int $limit = 5): Collection
    {
        return $this->model->select('user_id', DB::raw('SUM(points) as total_points'))
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->with('user:id,name,phone')
            ->get();
    }

    public function recentByType(string $type, int $limit = 10): Collection
    {
        return $this->model->where('type', $type)
            ->with(['user:id,name', 'booking'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
