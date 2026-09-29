<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

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
}
