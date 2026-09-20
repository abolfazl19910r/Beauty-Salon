<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BeautyServiceRepositoryInterface extends RepositoryInterface
{
    public function paginateForIndex(?int $categoryId, int $perPage = 12): LengthAwarePaginator;

    public function paginateWithCategory(int $perPage = 10): LengthAwarePaginator;

    public function getRelated(int $categoryId, int $excludeId, int $limit = 3): Collection;
}
