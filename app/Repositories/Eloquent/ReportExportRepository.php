<?php

namespace App\Repositories\Eloquent;

use App\Models\ReportExport;
use App\Repositories\Contracts\ReportExportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportExportRepository extends BaseRepository implements ReportExportRepositoryInterface
{
    public function __construct(ReportExport $model)
    {
        parent::__construct($model);
    }

    public function paginateWithAdminUser(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->with('adminUser')
            ->latest()
            ->paginate($perPage);
    }

    public function getOlderThanWithStatuses(array $statuses, \DateTimeInterface $before): Collection
    {
        return $this->model->whereIn('status', $statuses)
            ->where('created_at', '<=', $before)
            ->get();
    }

    public function deleteByIds(iterable $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}
