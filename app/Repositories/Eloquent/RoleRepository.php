<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function findByNameOrFail(string $name): Role
    {
        return $this->model->where('name', $name)->firstOrFail();
    }

    public function getAssignable(bool $includeSuperAdmin): Collection
    {
        return $this->model
            ->when(! $includeSuperAdmin, fn ($q) => $q->where('name', '!=', 'super-admin'))
            ->get();
    }

    public function paginateWithUserCount(bool $includeSuperAdmin, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->withCount(['users' => fn ($q) => $q->whereIn('users.id', app(UserRepositoryInterface::class)->querySalonMembers()->select('users.id'))])
            ->when(! $includeSuperAdmin, fn ($q) => $q->where('name', '!=', 'super-admin'))
            ->paginate($perPage);
    }

    public function getIdByName(string $name): ?int
    {
        return $this->model->where('name', $name)->value('id');
    }

    public function getIdsByNames(array $names): array
    {
        return $this->model->whereIn('name', $names)->pluck('id')->all();
    }

    public function getTopByUserCount(int $limit = 4): Collection
    {
        return $this->model->withCount(['users' => fn ($q) => $q->whereIn('users.id', app(UserRepositoryInterface::class)->querySalonMembers()->select('users.id'))])->take($limit)->get();
    }

    public function firstOrCreateByName(string $name, array $defaults): Role
    {
        return $this->model->firstOrCreate(['name' => $name], $defaults);
    }

    public function getSystemRoleNames(): array
    {
        return $this->model->newQuery()->withoutGlobalScopes()->whereNull('salon_id')->pluck('name')->all();
    }
}
