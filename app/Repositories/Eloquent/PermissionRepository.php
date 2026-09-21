<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function findByNameOrFail(string $name): Permission
    {
        return $this->model->where('name', $name)->firstOrFail();
    }

    public function getAllGroupedByGroup(): Collection
    {
        return $this->model->all()->groupBy('group');
    }

    public function paginateOrderedByGroupAndName(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->orderBy('group')->orderBy('name')->paginate($perPage);
    }

    public function getDistinctGroups(): SupportCollection
    {
        return $this->model->select('group')->distinct()->pluck('group');
    }
}
