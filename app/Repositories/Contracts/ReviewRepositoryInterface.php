<?php

namespace App\Repositories\Contracts;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface ReviewRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function paginateApprovedForSpecialist(int $specialistId, int $perPage = 10): LengthAwarePaginator;

    public function paginateForSpecialistWithFilters(int $specialistId, array $filters, int $perPage = 10): LengthAwarePaginator;

    public function countApproved(): int;

    public function countNegative(): int;

    public function avgOverallRating(): ?float;

    public function findWithTrashedOrFail(int $id): Review;

    public function getRatingDistribution(): SupportCollection;

    public function getRecentNegative(int $limit = 5): Collection;

    public function getMonthlyStats(int $limit = 12): Collection;

    public function paginateTrashed(int $perPage = 15): LengthAwarePaginator;

    public function calculateSpecialistAverage(int $specialistId): float;

    /**
     * @return array{total: int, average: float, quality_avg: float, behavior_avg: float,
     *     cleanliness_avg: float, speed_avg: float, five_star: int, four_star: int,
     *     three_star: int, two_star: int, one_star: int}
     */
    public function getSpecialistStats(int $specialistId): array;
}
