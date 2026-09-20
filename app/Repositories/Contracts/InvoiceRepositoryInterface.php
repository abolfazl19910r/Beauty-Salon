<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    public function paginateForSalon(int $salonId, int $perPage = 15): LengthAwarePaginator;
}
