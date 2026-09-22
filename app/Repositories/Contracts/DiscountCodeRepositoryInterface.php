<?php

namespace App\Repositories\Contracts;

use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DiscountCodeRepositoryInterface extends RepositoryInterface
{
    public function paginateWithUser(int $perPage = 15): LengthAwarePaginator;

    /**
     * @return array{total: int, active: int, expired: int, used_up: int}
     */
    public function getStats(): array;

    public function findByCode(string $code): ?DiscountCode;

    public function lockByCode(string $code): ?DiscountCode;

    public function getActiveForUser(int $userId): Collection;

    public function getExpiredForUser(int $userId): Collection;

    public function getTypesByCodes(iterable $codes): \Illuminate\Support\Collection;
}
