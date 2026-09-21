<?php

namespace App\Repositories\Eloquent;

use App\Models\DiscountCode;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DiscountCodeRepository extends BaseRepository implements DiscountCodeRepositoryInterface
{
    public function __construct(DiscountCode $model)
    {
        parent::__construct($model);
    }

    public function paginateWithUser(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('user')
            ->latest()
            ->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->whereColumn('used_count', '<', 'max_uses')
                ->count(),
            'expired' => $this->model->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
            'used_up' => $this->model->whereColumn('used_count', '>=', 'max_uses')->count(),
        ];
    }

    public function findByCode(string $code): ?DiscountCode
    {
        return $this->model->where('code', $code)->first();
    }

    public function lockByCode(string $code): ?DiscountCode
    {
        return $this->model->where('code', $code)->lockForUpdate()->first();
    }

    public function getActiveForUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('used_count', '<', DB::raw('max_uses'))
            ->latest()
            ->get();
    }

    public function getTypesByCodes(iterable $codes): \Illuminate\Support\Collection
    {
        return $this->model->whereIn('code', $codes)->pluck('type', 'code');
    }
}
