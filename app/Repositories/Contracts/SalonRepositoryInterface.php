<?php

namespace App\Repositories\Contracts;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SalonRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Salon;

    public function slugExists(string $slug): bool;

    public function lockForUpdateFindOrFail(int|string $id): Salon;

    public function getOldestSlug(): ?string;

    public function getAllWithSpecialistCountAndAdmins(): Collection;

    public function paginateWithSpecialistCountAndAdmins(int $perPage = 20): LengthAwarePaginator;
}
