<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getMaxOrder(?int $parentId): int
    {
        return (int) $this->model->where('parent_id', $parentId)->max('order');
    }

    public function updateOrder(int|string $id, int $order): void
    {
        $this->model->where('id', $id)->update(['order' => $order]);
    }

    public function getTree(): Collection
    {
        return $this->model->with('children')
            ->parents()
            ->orderBy('order')
            ->get();
    }

    public function getActiveTree(): Collection
    {
        return $this->model->with(['children' => function ($query) {
            $query->where('is_active', true)->orderBy('order');
        }])
            ->parents()
            ->active()
            ->orderBy('order')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with('parent');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->orderBy('order', 'asc')->paginate($perPage);
    }

    public function getParentOptions(): Collection
    {
        return $this->model->parents()->get(['id', 'name']);
    }

    public function getOptionsExcept(int|string $excludeId): Collection
    {
        return $this->model->where('id', '!=', $excludeId)->get(['id', 'name']);
    }

    public function getWithServices(): Collection
    {
        return $this->model->with('services')->get();
    }
}
