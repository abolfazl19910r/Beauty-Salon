<?php

namespace App\Repositories\Eloquent;

use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletTransactionRepository extends BaseRepository implements WalletTransactionRepositoryInterface
{
    public function __construct(WalletTransaction $model)
    {
        parent::__construct($model);
    }

    public function paginateForWalletWithFilters(int $walletId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->where('wallet_id', $walletId)->with('booking');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function getRecentForWallet(int $walletId, int $limit = 10): Collection
    {
        return $this->model->where('wallet_id', $walletId)->latest()->limit($limit)->get();
    }

    public function sumForWalletByTypeAndMonth(int $walletId, string $type, int $month, int $year): float
    {
        return (float) $this->model->where('wallet_id', $walletId)
            ->where('type', $type)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('amount');
    }

    public function getPendingIncomeForSettlement(?int $walletId = null): Collection
    {
        $query = $this->model->where('type', 'income')
            ->whereJsonContains('metadata->status', 'pending');

        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        return $query->get();
    }
}
