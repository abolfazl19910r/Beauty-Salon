<?php

namespace App\Services\Admin\DiscountCode;

use App\Models\DiscountCode;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Services\Discount\DiscountCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AdminDiscountCodeService
{
    public function __construct(
        private readonly DiscountCalculator $calculator,
        private readonly DiscountCodeRepositoryInterface $discountCodeRepository,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->discountCodeRepository->paginateWithUser($perPage);
    }

    /**
     * @return array{total: int, active: int, expired: int, used_up: int}
     */
    public function stats(): array
    {
        return $this->discountCodeRepository->getStats();
    }

    public function store(array $data): DiscountCode
    {
        $data['code'] = Str::upper(trim($data['code']));
        $data['used_count'] = 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return $this->discountCodeRepository->create($data);
    }

    public function update(DiscountCode $discountCode, array $data): DiscountCode
    {
        $discountCode = $this->discountCodeRepository->update($discountCode, $data);

        return $discountCode->fresh();
    }

    public function destroy(DiscountCode $discountCode): void
    {
        if ($discountCode->used_count > 0) {
            throw new \RuntimeException(
                'این کد تخفیف قبلاً استفاده شده و برای حفظ سوابق مالی قابل حذف نیست؛ می‌توانید آن را غیرفعال کنید.'
            );
        }

        $this->discountCodeRepository->delete($discountCode);
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
