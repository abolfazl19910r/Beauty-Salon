<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogCategoryRepository extends BaseRepository implements BlogCategoryRepositoryInterface
{
    public function __construct(BlogCategory $model)
    {
        parent::__construct($model);
    }

    public function getAllOrdered(): Collection
    {
        return $this->model->orderBy('order')->orderBy('name')->get();
    }

    public function getWithPostsCount(): Collection
    {
        return $this->model->withCount('posts')
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    public function paginateWithPostsCount(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->withCount('posts')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getMaxOrder(): int
    {
        return (int) $this->model->max('order');
    }

    public function hasPosts(BlogCategory $category): bool
    {
        return $category->posts()->exists();
    }
}
