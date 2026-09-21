<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlogPostRepositoryInterface extends RepositoryInterface
{
    public function paginateWithCategory(int $perPage = 15): LengthAwarePaginator;

    public function sumViews(): int;

    public function paginatePublished(?int $categoryId, int $perPage = 9): LengthAwarePaginator;

    public function getRelatedPublished(int $categoryId, int $excludeId, int $limit = 3): Collection;
}
