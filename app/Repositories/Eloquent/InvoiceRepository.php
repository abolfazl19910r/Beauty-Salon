<?php

namespace App\Repositories\Eloquent;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function paginateForSalon(int $salonId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('salon_id', $salonId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
