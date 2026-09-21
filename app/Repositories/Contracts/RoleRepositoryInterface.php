<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface extends RepositoryInterface
{
    public function findByNameOrFail(string $name): Role;

    public function getAssignable(bool $includeSuperAdmin): Collection;

    public function paginateWithUserCount(bool $includeSuperAdmin, int $perPage = 10): LengthAwarePaginator;

    public function getIdByName(string $name): ?int;

    public function getIdsByNames(array $names): array;

    public function getTopByUserCount(int $limit = 4): Collection;

    public function firstOrCreateByName(string $name, array $defaults): Role;
}
