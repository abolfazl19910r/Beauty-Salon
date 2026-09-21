<?php

namespace App\Repositories\Contracts;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    public function query(): Builder;

    public function findByNameOrFail(string $name): Permission;

    public function getAllGroupedByGroup(): Collection;

    public function paginateOrderedByGroupAndName(int $perPage = 20): LengthAwarePaginator;

    public function getDistinctGroups(): SupportCollection;
}
