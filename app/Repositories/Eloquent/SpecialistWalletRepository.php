<?php

namespace App\Repositories\Eloquent;

use App\Models\SpecialistWallet;
use App\Repositories\Contracts\SpecialistWalletRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class SpecialistWalletRepository extends BaseRepository implements SpecialistWalletRepositoryInterface
{
    public function __construct(SpecialistWallet $model)
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
        $query = $this->forCurrentSalon()->with('specialist');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('specialist', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        match ($filters['sort_by'] ?? 'balance_desc') {
            'balance_asc' => $query->orderBy('balance', 'asc'),
            'earned_desc' => $query->orderBy('total_earned', 'desc'),
            default => $query->orderBy('balance', 'desc'),
        };

        return $query->paginate($perPage);
    }

    public function getTotals(): array
    {
        return [
            'totalBalance' => $this->forCurrentSalon()->sum('balance'),
            'totalEarned' => $this->forCurrentSalon()->sum('total_earned'),
            'totalWithdrawn' => $this->forCurrentSalon()->sum('total_withdrawn'),
            'totalPending' => $this->forCurrentSalon()->sum('pending_amount'),
        ];
    }

    public function lockById(int $id): ?SpecialistWallet
    {
        return $this->model->whereKey($id)->lockForUpdate()->first();
    }
}
