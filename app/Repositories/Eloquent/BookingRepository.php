<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    public function __construct(Booking $model)
    {
        parent::__construct($model);
    }

    public function paginateForUser(int $userId, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['service', 'specialist'])
            ->where('user_id', $userId)
            ->orderByRaw("
                CASE `status`
                    WHEN 'confirmed' THEN 1
                    WHEN 'completed' THEN 1
                    WHEN 'pending' THEN 2
                    WHEN 'pending_payment' THEN 2
                    WHEN 'cancelled' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('booking_time', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('booking_time', $filters['date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForUserWithDetails(int $id, int $userId): ?Booking
    {
        return $this->model->with(['service', 'specialist'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function findForUser(int $id, int $userId): ?Booking
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }

    public function getAllForUser(int $userId): Collection
    {
        return $this->model->with(['service', 'specialist'])
            ->where('user_id', $userId)
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    public function getUpcomingExcludingCancelledForUser(int $userId): Collection
    {
        return $this->model->with(['service', 'specialist'])
            ->where('user_id', $userId)
            ->where('booking_time', '>', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('booking_time', 'asc')
            ->get();
    }

    public function getPastForUserApi(int $userId): Collection
    {
        return $this->model->with(['service', 'specialist'])
            ->where('user_id', $userId)
            ->where('booking_time', '<=', now())
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    public function getLatestSuccessfulForUser(int $userId): Booking
    {
        return $this->model->with(['service', 'specialist'])
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->latest('paid_at')
            ->firstOrFail();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'specialist', 'service'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('booking_time', $filters['date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getStats(array $filters): array
    {
        $statsQuery = $this->model->query();

        if (! empty($filters['date'])) {
            $statsQuery->whereDate('booking_time', $filters['date']);
        }

        return [
            'total' => (clone $statsQuery)->count(),
            'confirmed' => (clone $statsQuery)->where('status', 'confirmed')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];
    }

    public function getUpcomingForUser(int $userId, int $limit = 5): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('booking_time', '>', Carbon::now())
            ->whereNotIn('status', ['cancelled'])
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->limit($limit)
            ->get();
    }

    public function getPastForUser(int $userId, int $limit = 5): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('booking_time', '<', Carbon::now())
            ->with(['service', 'specialist'])
            ->orderBy('booking_time', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRatingStatsForSpecialist(int $specialistId): array
    {
        $query = $this->model->where('specialist_id', $specialistId);

        return [
            'avg' => (clone $query)->whereNotNull('rating')->avg('rating'),
            'count' => (clone $query)->whereNotNull('rating')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];
    }

    public function getRecentReviewsForSpecialist(int $specialistId, int $limit = 5): Collection
    {
        return $this->model->where('specialist_id', $specialistId)
            ->with('user:id,name')
            ->whereNotNull('review')
            ->whereNotNull('rating')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function hasBookingInRange(int $specialistId, string $startDateTime, string $endDateTime): bool
    {
        return $this->model->where('specialist_id', $specialistId)
            ->whereBetween('booking_time', [$startDateTime, $endDateTime])
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    public function hasBookingOnDate(int $specialistId, string $date): bool
    {
        return $this->model->where('specialist_id', $specialistId)
            ->whereDate('booking_time', $date)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }
}
