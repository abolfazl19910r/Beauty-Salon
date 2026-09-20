<?php

namespace App\Repositories\Contracts;

use App\Models\WithdrawalRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface WithdrawalRequestRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getStats(): array;

    public function lockById(int $id): ?WithdrawalRequest;

    public function paginateForWallet(int $walletId, int $perPage = 10): LengthAwarePaginator;
}
