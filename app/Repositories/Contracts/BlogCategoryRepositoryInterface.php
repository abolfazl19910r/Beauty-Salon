<?php

namespace App\Repositories\Contracts;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlogCategoryRepositoryInterface extends RepositoryInterface
{
    public function getAllOrdered(): Collection;

    public function getWithPostsCount(): Collection;

    public function paginateWithPostsCount(int $perPage = 15): LengthAwarePaginator;

    public function getMaxOrder(): int;

    public function hasPosts(BlogCategory $category): bool;
}
