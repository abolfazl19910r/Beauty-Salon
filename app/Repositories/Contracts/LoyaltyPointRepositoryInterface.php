<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface LoyaltyPointRepositoryInterface extends RepositoryInterface
{
    public function sumForUser(int $userId): int;

    public function sumExpiringForUser(int $userId, int $days): int;

    public function sumExpiringSoonForUser(int $userId, int $days): int;

    public function sumForUserByType(int $userId, string $type): int;

    public function sumByType(string $type, ?int $salonId = null): int;

    public function countDistinctUsers(?int $salonId = null): int;

    public function paginateForUserWithBooking(int $userId, int $perPage = 10): LengthAwarePaginator;

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator;
}
