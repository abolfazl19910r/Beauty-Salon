<?php

namespace App\Services\Payment;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use App\Payments\PayoutManager;
use App\Payments\PayoutRequest;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ تسویه‌ی خودکار برداشت متخصص (قبلاً SalonPayoutService؛ مرحله‌ی ۳ چند درگاه، ۲۰۲۶-۰۹-۲۵).
 *
 * پول همیشه از حساب درگاه **خودِ سالنِ** متخصص می‌ره (تصمیم ابوالفضل، ۲۰۲۶-۰۹-۲۴)، نه از حساب پلتفرم.
 * انتخاب درگاه و تماس HTTP با App\Payments\PayoutManager و driverهای تسویه است؛ این کلاس فقط سالنِ
 * درخواست رو پیدا می‌کنه و خروجی رو به شکلی که ProcessWithdrawalJob از قبل انتظار داره برمی‌گردونه.
 */
class SalonPayoutService
{
    public function __construct(private readonly PayoutManager $payouts) {}

    public function isConfiguredFor(?Salon $salon): bool
    {
        return $this->payouts->driverFor($salon) !== null;
    }

    private function salonOf(WithdrawalRequest $withdrawalRequest): ?Salon
    {
        $salonId = Specialist::withoutGlobalScopes()->whereKey($withdrawalRequest->specialist_id)->value('salon_id');

        return $salonId ? Salon::withoutGlobalScopes()->find($salonId) : null;
    }

    public function payout(WithdrawalRequest $withdrawalRequest): array
    {
        $salon = $this->salonOf($withdrawalRequest);
        $driver = $this->payouts->driverFor($salon);

        if (! $driver) {
            Log::warning('SalonPayoutService: تسویه‌ی خودکار برای این سالن پیکربندی نشده', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'salon_id' => $salon?->id,
            ]);

            return [
                'success' => false,
                'message' => 'تسویه‌ی خودکار برای این سالن فعال نیست (در «درگاه‌های پرداخت»، اطلاعات تسویه‌ی زرین‌پال یا زیبال سالن وارد نشده). درخواست را دستی تسویه کنید.',
            ];
        }

        return $driver->payout(new PayoutRequest(
            (int) (($withdrawalRequest->net_amount ?? $withdrawalRequest->amount) * 10),
            (string) $withdrawalRequest->iban,
            sprintf('تسویه حساب متخصص: %s (درخواست #%d)', $withdrawalRequest->specialist?->name ?? '—', $withdrawalRequest->id),
            (string) $withdrawalRequest->id,
        ))->toArray();
    }
}
