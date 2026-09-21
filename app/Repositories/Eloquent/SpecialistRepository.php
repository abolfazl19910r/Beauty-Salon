<?php

namespace App\Repositories\Eloquent;

use App\Models\Specialist;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SpecialistRepository extends BaseRepository implements SpecialistRepositoryInterface
{
    public function __construct(Specialist $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->whereNull('deleted_at')
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['bookings' => function ($query) {
                $query->whereDate('booking_time', today());
            }])
            ->with('services:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function searchPaginated(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->whereNull('deleted_at');

        if (array_key_exists('name', $filters)) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (array_key_exists('service_id', $filters)) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('beauty_services.id', $filters['service_id']);
            });
        }

        if (array_key_exists('sort', $filters)) {
            if ($filters['sort'] === 'rating') {
                $query->withCount(['bookings as total_ratings' => function ($q) {
                    $q->whereNotNull('rating');
                }])
                    ->withAvg('bookings', 'rating')
                    ->orderBy('bookings_avg_rating', $filters['direction'] ?? 'desc');
            } else {
                $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc');
            }
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getTopRated(int $limit = 10): Collection
    {
        return $this->model->whereNull('deleted_at')
            ->withCount(['bookings as completed_bookings' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withCount(['bookings as rating_count' => function ($query) {
                $query->whereNotNull('rating');
            }])
            ->withAvg('bookings', 'rating')
            ->having('bookings_avg_rating', '>=', 4)
            ->having('rating_count', '>=', 5)
            ->orderByDesc('bookings_avg_rating')
            ->orderByDesc('rating_count')
            ->take($limit)
            ->get();
    }

    public function findByPhone(string $phone): ?Specialist
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findByPhoneOrFail(string $phone): Specialist
    {
        return $this->model->where('phone', $phone)->firstOrFail();
    }

    public function getNameOptions(): Collection
    {
        return $this->model->select('id', 'name')->orderBy('name')->get();
    }

    public function getTopRatedByApprovedReviews(int $limit = 10): Collection
    {
        return $this->model->withCount(['reviews' => function ($q) {
            $q->where('is_approved', true);
        }])
            ->withAvg(['reviews' => function ($q) {
                $q->where('is_approved', true);
            }], 'overall_rating')
            ->having('reviews_count', '>=', 1)
            ->orderByDesc('reviews_avg_overall_rating')
            ->limit($limit)
            ->get()
            ->map(function ($s) {
                $s->reviews_avg_overall_rating = round($s->reviews_avg_overall_rating ?? 0, 1);

                return $s;
            });
    }

    public function getSalonIdIgnoringScopes(int $specialistId): ?int
    {
        return $this->model->withoutGlobalScopes()->whereKey($specialistId)->value('salon_id');
    }

    public function countBySalonIgnoringScope(int $salonId): int
    {
        return $this->model->withoutGlobalScope('salon')->where('salon_id', $salonId)->count();
    }

    public function paginateByService(int $serviceId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->whereNull('deleted_at')
            ->whereHas('services', function ($query) use ($serviceId) {
                $query->where('beauty_services.id', $serviceId);
            })
            ->withCount(['bookings as completed_bookings' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withAvg('bookings', 'rating')
            ->paginate($perPage);
    }
}
