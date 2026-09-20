<?php

namespace App\Repositories\Contracts;

use App\Models\Leave;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function paginateForSpecialist(int $specialistId, int $perPage = 10): LengthAwarePaginator;

    public function getPending(): Collection;

    public function createForSpecialist(Specialist $specialist, array $data): Leave;

    public function hasOverlappingApprovedLeave(int $specialistId, string $startDate, string $endDate, ?int $excludeLeaveId = null): bool;

    public function hasApprovedLeaveOnDate(int $specialistId, string $date): bool;
}
