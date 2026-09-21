<?php

namespace App\Repositories\Eloquent;

use App\Models\BeautyService;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BeautyServiceRepository extends BaseRepository implements BeautyServiceRepositoryInterface
{
    public function __construct(BeautyService $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function paginateForIndex(?int $categoryId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model->with('category')
            ->when($categoryId, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateWithCategory(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with('category')->latest()->paginate($perPage);
    }

    public function getRelated(int $categoryId, int $excludeId, int $limit = 3): Collection
    {
        return $this->model->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->limit($limit)
            ->get();
    }

    public function getLatest(int $limit): Collection
    {
        return $this->model->latest()->take($limit)->get();
    }
}
