<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface extends RepositoryInterface
{
    public function paginateForUser(int $userId, array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findForUserWithDetails(int $id, int $userId): ?Booking;

    public function findOrFailWithReviewDetails(int $id): Booking;

    public function findForUser(int $id, int $userId): ?Booking;

    public function getAllForUser(int $userId): Collection;

    public function getUpcomingExcludingCancelledForUser(int $userId): Collection;

    public function getPastForUserApi(int $userId): Collection;

    public function getLatestSuccessfulForUser(int $userId): Booking;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getStats(array $filters): array;

    public function getUpcomingForUser(int $userId, int $limit = 5): Collection;

    public function getPastForUser(int $userId, int $limit = 5): Collection;

    public function getRatingStatsForSpecialist(int $specialistId): array;

    public function getRecentReviewsForSpecialist(int $specialistId, int $limit = 5): Collection;

    public function hasBookingInRange(int $specialistId, string $startDateTime, string $endDateTime): bool;

    public function hasBookingOnDate(int $specialistId, string $date): bool;
}
