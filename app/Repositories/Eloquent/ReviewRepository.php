<?php

namespace App\Repositories\Eloquent;

use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ReviewRepository extends BaseRepository implements ReviewRepositoryInterface
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'specialist', 'service', 'booking']);

        if (! empty($filters['specialist_id'])) {
            $query->where('specialist_id', $filters['specialist_id']);
        }

        if (! empty($filters['rating'])) {
            $query->where('overall_rating', $filters['rating']);
        }

        if (array_key_exists('is_approved', $filters)) {
            $query->where('is_approved', $filters['is_approved'] === '1');
        }

        if (array_key_exists('negative', $filters)) {
            $query->negative();
        }

        if (array_key_exists('has_response', $filters)) {
            if ($filters['has_response'] === '1') {
                $query->whereNotNull('specialist_response');
            } else {
                $query->whereNull('specialist_response');
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('specialist', function ($specQuery) use ($search) {
                        $specQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        match ($filters['sort_by'] ?? 'latest') {
            'oldest' => $query->oldest('reviewed_at'),
            'highest_rating' => $query->orderBy('overall_rating', 'desc'),
            'lowest_rating' => $query->orderBy('overall_rating', 'asc'),
            default => $query->latest('reviewed_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateApprovedForSpecialist(int $specialistId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with(['user', 'service'])
            ->where('specialist_id', $specialistId)
            ->approved()
            ->recent()
            ->paginate($perPage);
    }

    public function paginateForSpecialistWithFilters(int $specialistId, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'service', 'booking'])
            ->where('specialist_id', $specialistId);

        if (! empty($filters['rating'])) {
            $query->where('overall_rating', $filters['rating']);
        }

        if (array_key_exists('responded', $filters)) {
            if ($filters['responded'] === '1') {
                $query->whereNotNull('specialist_response');
            } else {
                $query->whereNull('specialist_response');
            }
        }

        if (! empty($filters['date_from'])) {
            $query->where('reviewed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('reviewed_at', '<=', $filters['date_to']);
        }

        match ($filters['sort_by'] ?? 'latest') {
            'oldest' => $query->oldest('reviewed_at'),
            'highest_rating' => $query->orderBy('overall_rating', 'desc'),
            'lowest_rating' => $query->orderBy('overall_rating', 'asc'),
            default => $query->latest('reviewed_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function countApproved(): int
    {
        return $this->model->approved()->count();
    }

    public function countNegative(): int
    {
        return $this->model->negative()->count();
    }

    public function avgOverallRating(): ?float
    {
        return $this->model->avg('overall_rating');
    }

    public function findWithTrashedOrFail(int $id): Review
    {
        return $this->model->withTrashed()->findOrFail($id);
    }

    public function getRatingDistribution(): SupportCollection
    {
        return $this->model->select('overall_rating', DB::raw('count(*) as count'))
            ->groupBy('overall_rating')
            ->orderBy('overall_rating', 'desc')
            ->get()
            ->pluck('count', 'overall_rating');
    }

    public function getRecentNegative(int $limit = 5): Collection
    {
        return $this->model->with(['user', 'specialist', 'service'])
            ->negative()
            ->latest('reviewed_at')
            ->limit($limit)
            ->get();
    }

    public function getMonthlyStats(int $limit = 12): Collection
    {
        return $this->model->select(
            DB::raw('DATE_FORMAT(reviewed_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(overall_rating) as avg_rating')
        )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit($limit)
            ->get();
    }

    public function paginateTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->onlyTrashed()
            ->with(['user', 'specialist', 'service'])
            ->latest('deleted_at')
            ->paginate($perPage);
    }

    public function calculateSpecialistAverage(int $specialistId): float
    {
        return $this->model->where('specialist_id', $specialistId)
            ->approved()
            ->avg('overall_rating') ?? 0;
    }

    public function getSpecialistStats(int $specialistId): array
    {
        $reviews = $this->model->where('specialist_id', $specialistId)->approved();

        return [
            'total' => $reviews->count(),
            'average' => round($reviews->avg('overall_rating') ?? 0, 1),
            'quality_avg' => round($reviews->avg('quality_rating') ?? 0, 1),
            'behavior_avg' => round($reviews->avg('behavior_rating') ?? 0, 1),
            'cleanliness_avg' => round($reviews->avg('cleanliness_rating') ?? 0, 1),
            'speed_avg' => round($reviews->avg('speed_rating') ?? 0, 1),
            'five_star' => $reviews->clone()->where('overall_rating', 5)->count(),
            'four_star' => $reviews->clone()->where('overall_rating', 4)->count(),
            'three_star' => $reviews->clone()->where('overall_rating', 3)->count(),
            'two_star' => $reviews->clone()->where('overall_rating', 2)->count(),
            'one_star' => $reviews->clone()->where('overall_rating', 1)->count(),
        ];
    }
}
