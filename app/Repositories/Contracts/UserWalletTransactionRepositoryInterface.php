<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserWalletTransactionRepositoryInterface extends RepositoryInterface
{
    public function paginateForWalletWithFilters(int $walletId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getRecentForWallet(int $walletId, int $limit = 10): Collection;

    public function sumForWalletByTypeAndMonth(int $walletId, string $type, int $month, int $year): float;
}
