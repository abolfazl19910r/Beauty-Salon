<?php

namespace App\Repositories\Contracts;

use App\Models\SpecialistWallet;
use Illuminate\Pagination\LengthAwarePaginator;

interface SpecialistWalletRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getTotals(): array;

    public function lockById(int $id): ?SpecialistWallet;
}
