<?php

namespace App\Services\Admin\DiscountCode;

use App\Models\DiscountCode;
use App\Services\Discount\DiscountCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * R-AdminDiscountCode: independent admin panel for manually creating/managing discount codes,
 * separate from the existing loyalty-redemption path (LoyaltyService::redeemReward()) which
 * remains the only place codes are created automatically. This service never reimplements the
 * discount formula itself — App\Services\Discount\DiscountCalculator (the project's single source
 * of discount math, per R-DiscountLogic) is used for the preview feature below.
 */
class AdminDiscountCodeService
{
    public function __construct(
        private readonly DiscountCalculator $calculator,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return DiscountCode::with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return array{total: int, active: int, expired: int, used_up: int}
     */
    public function stats(): array
    {
        return [
            'total' => DiscountCode::count(),
            'active' => DiscountCode::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->whereColumn('used_count', '<', 'max_uses')
                ->count(),
            'expired' => DiscountCode::whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
            'used_up' => DiscountCode::whereColumn('used_count', '>=', 'max_uses')->count(),
        ];
    }

    public function store(array $data): DiscountCode
    {
        $data['code'] = Str::upper(trim($data['code']));
        $data['used_count'] = 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return DiscountCode::create($data);
    }

    /**
     * UpdateDiscountCodeRequest deliberately only validates is_active/expires_at/max_uses (see
     * R2's documented note on this file) — code/type/amount/user_id are immutable after creation,
     * so a code's discount math can never silently change out from under bookings that already
     * reference it by its string `code` value.
     */
    public function update(DiscountCode $discountCode, array $data): DiscountCode
    {
        $discountCode->update($data);

        return $discountCode->fresh();
    }

    /**
     * Codes that have already been used are kept (not hard-deleted) for financial/audit
     * traceability — the only way to retire one is to deactivate it (is_active=false), which
     * DiscountCode::isValid() already respects.
     */
    public function destroy(DiscountCode $discountCode): void
    {
        if ($discountCode->used_count > 0) {
            throw new \RuntimeException(
                'این کد تخفیف قبلاً استفاده شده و برای حفظ سوابق مالی قابل حذف نیست؛ می‌توانید آن را غیرفعال کنید.'
            );
        }

        $discountCode->delete();
    }

    /**
     * @return array{discount_amount: float, final_amount: float}
     */
    public function preview(string $type, float $amount, ?float $maxAmount, float $baseAmount): array
    {
        $transientCode = new DiscountCode([
            'type' => $type,
            'amount' => $amount,
            'max_amount' => $maxAmount,
        ]);

        return $this->calculator->calculate($transientCode, $baseAmount);
    }
}
