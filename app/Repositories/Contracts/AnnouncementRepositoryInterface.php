<?php

namespace App\Repositories\Contracts;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AnnouncementRepositoryInterface extends RepositoryInterface
{
    public function getActive(): Collection;

    public function getTopActive(): ?Announcement;

    public function paginateActive(int $perPage = 10): LengthAwarePaginator;

    public function findActiveOrFail(int $id): Announcement;

    public function paginateAllOrdered(int $perPage = 15): LengthAwarePaginator;

    public function countActive(): int;

    public function countPending(): int;

    public function countExpired(): int;
}
