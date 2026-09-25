<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SecurityLogRepositoryInterface extends RepositoryInterface
{
    public function query(): Builder;

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function paginateForUserWithFilters(int $userId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getRecentForUser(int $userId, int $limit = 5): Collection;

    public function getLoginHistoryForUser(int $userId, int $limit = 10): Collection;

    public function getLastSuccessfulLoginAt(int $userId): mixed;

    public function countWarningsForUserSince(int $userId, \DateTimeInterface $since): int;

    public function countLoginAttemptsForUserSince(int $userId, \DateTimeInterface $since): int;
}
