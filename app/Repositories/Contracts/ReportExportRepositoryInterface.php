<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReportExportRepositoryInterface extends RepositoryInterface
{
    public function paginateWithAdminUser(int $perPage = 20): LengthAwarePaginator;

    public function getOlderThanWithStatuses(array $statuses, \DateTimeInterface $before): Collection;

    public function deleteByIds(iterable $ids): int;
}
