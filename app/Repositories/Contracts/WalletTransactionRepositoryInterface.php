<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface WalletTransactionRepositoryInterface extends RepositoryInterface
{
    public function paginateForWalletWithFilters(int $walletId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getRecentForWallet(int $walletId, int $limit = 10): Collection;

    public function sumForWalletByTypeBetween(int $walletId, string $type, \DateTimeInterface $from, \DateTimeInterface $to): float;

    public function sumNetIncomeForWalletBetween(int $walletId, \DateTimeInterface $from, \DateTimeInterface $to): float;

    /** @return array<string, float> */
    public function netIncomeByDayForWallet(int $walletId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    public function lazyPendingIncomeForSettlement(?int $walletId = null, int $chunkSize = 1000): LazyCollection;
}
