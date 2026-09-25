<?php

namespace App\Repositories\Eloquent;

use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class WithdrawalRequestRepository extends BaseRepository implements WithdrawalRequestRepositoryInterface
{
    public function __construct(WithdrawalRequest $model)
    {
        parent::__construct($model);
    }

    /**
     * ⭐ فقط متخصص‌های سالن فعلی (۲۰۲۶-۰۹-۲۶): این جدول ستون salon_id نداره و مدل scope نداره؛ whereHas('specialist')
     * scope سراسری BelongsToSalon متخصص رو اعمال می‌کنه. قبلاً فهرست و آمار این صفحه‌ها رکوردهای همه‌ی سالن‌ها رو
     * نشون می‌داد (با probe بازتولید شد) — فقط صفحه‌ی جزئیات چک مالکیت داشت.
     */
    private function forCurrentSalon()
    {
        return $this->model->newQuery()->whereHas('specialist');
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->forCurrentSalon()->with(['specialist', 'wallet']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                    ->orWhereHas('specialist', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'pendingCount' => $this->forCurrentSalon()->where('status', 'pending')->count(),
            'pendingAmount' => $this->forCurrentSalon()->where('status', 'pending')->sum('amount'),
            'completedToday' => $this->forCurrentSalon()->where('status', 'completed')
                ->whereDate('processed_at', today())
                ->count(),
        ];
    }

    public function lockById(int $id): ?WithdrawalRequest
    {
        return $this->model->whereKey($id)->lockForUpdate()->first();
    }

    public function paginateForWallet(int $walletId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('wallet_id', $walletId)->latest()->paginate($perPage);
    }
}
