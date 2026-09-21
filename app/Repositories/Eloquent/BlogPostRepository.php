<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogPostRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogPostRepository extends BaseRepository implements BlogPostRepositoryInterface
{
    public function __construct(BlogPost $model)
    {
        parent::__construct($model);
    }

    public function paginateWithCategory(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('category')->latest()->paginate($perPage);
    }

    public function sumViews(): int
    {
        return (int) $this->model->sum('views');
    }

    public function paginatePublished(?int $categoryId, int $perPage = 9): LengthAwarePaginator
    {
        return $this->model->with('category')
            ->when($categoryId, function ($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getRelatedPublished(int $categoryId, int $excludeId, int $limit = 3): Collection
    {
        return $this->model->with('category')
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->take($limit)
            ->get();
    }
}
