<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function getMaxOrder(?int $parentId): int;

    public function updateOrder(int|string $id, int $order): void;

    public function getTree(): Collection;

    public function getActiveTree(): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function getParentOptions(): Collection;

    public function getOptionsExcept(int|string $excludeId): Collection;
}
