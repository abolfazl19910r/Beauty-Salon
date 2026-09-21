<?php

namespace App\Repositories\Eloquent;

use App\Models\Salon;
use App\Repositories\Contracts\SalonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalonRepository extends BaseRepository implements SalonRepositoryInterface
{
    public function __construct(Salon $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Salon
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function slugExists(string $slug): bool
    {
        return $this->model->where('slug', $slug)->exists();
    }

    public function lockForUpdateFindOrFail(int|string $id): Salon
    {
        return $this->model->lockForUpdate()->findOrFail($id);
    }

    public function getOldestSlug(): ?string
    {
        return $this->model->query()->oldest('id')->value('slug');
    }

    public function getAllWithSpecialistCountAndAdmins(): Collection
    {
        return $this->model->withCount('specialists')
            ->with('admins')
            ->orderByDesc('created_at')
            ->get();
    }

    public function paginateWithSpecialistCountAndAdmins(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->withCount('specialists')
            ->with('admins')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
