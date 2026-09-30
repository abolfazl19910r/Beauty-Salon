<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface WalletTransactionRepositoryInterface extends RepositoryInterface
{
    public function paginateForWalletWithFilters(int $walletId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getRecentForWallet(int $walletId, int $limit = 10): Collection;

    public function sumForWalletByTypeAndMonth(int $walletId, string $type, int $month, int $year): float;

    public function lazyPendingIncomeForSettlement(?int $walletId = null, int $chunkSize = 1000): LazyCollection;
}
