<?php

namespace App\Repositories\Eloquent;

use App\Models\Leave;
use App\Models\Specialist;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface
{
    public function __construct(Leave $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('specialist:id,name')
            ->whereHas('specialist')
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForSpecialist(int $specialistId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('specialist_id', $specialistId)->latest()->paginate($perPage);
    }

    public function getPending(): Collection
    {
        return $this->model->with('specialist')
            ->whereHas('specialist')
            ->pending()
            ->orderBy('start_date')
            ->get();
    }

    public function createForSpecialist(Specialist $specialist, array $data): Leave
    {
        return $specialist->leaves()->create($data);
    }

    public function hasOverlappingApprovedLeave(int $specialistId, string $startDate, string $endDate, ?int $excludeLeaveId = null): bool
    {
        return $this->model->where('specialist_id', $specialistId)
            ->where('status', 'approved')
            ->when($excludeLeaveId, fn ($q) => $q->where('id', '!=', $excludeLeaveId))
            ->overlapping($startDate, $endDate)
            ->exists();
    }

    public function hasApprovedLeaveOnDate(int $specialistId, string $date): bool
    {
        return $this->model->where('specialist_id', $specialistId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
