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

    /** @return array{active_salons: int, expiring_soon: int, expired: int} */
    public function subscriptionCounts(\DateTimeInterface $now): array;

    public function getRecentWithSpecialistCount(int $limit = 5): Collection;

    public function paginateWithSpecialistCountAndAdmins(int $perPage = 20): LengthAwarePaginator;
}
