<?php

namespace App\Services\Payment;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real connection to the ZarrinPal Payout API.
 *
 * Replaces the previous mock implementation in WalletAdminService::autoPayout() which always
 * returned $isSuccessful = true and a mock referenceCode without actually
 * transferring any money (see the "critical warning" at the top of Rasta_unified_prompt.md).
 *
 * ⚠️ Important note about sandboxing: ZarrinPal sandbox tests usually always return a simulated success response for Payout,
 * just like the payment gateway (documented behavior
 * by ZarrinPal, not a limitation of this class) — i.e. the sandbox only tests the "real HTTP path + real response parse
 *", not the actual authenticity of the funds transfer. Final testing with a small amount on
 * production is recommended.
 */
class ZarinpalPayoutService
{
    protected string $apiKey;

    protected string $merchantId;

    protected string $apiUrl;

    protected bool $sandbox;

    public function __construct()
    {
        $this->sandbox = (bool) config('services.zarinpal.payout.sandbox', true);

        $this->apiUrl = $this->sandbox
            ? config('services.zarinpal.payout.sandbox_base_url')
            : config('services.zarinpal.payout.base_url');
    }

    /**
     * ⭐ ۲۰۲۶-۰۹-۲۴ (تصمیم ابوالفضل): تسویه از حساب زرین‌پال **خودِ سالنِ** متخصص (کد پذیرنده +
     * توکن Payout سالن)، نه از حساب پلتفرم. قبلاً merchant_id و api_key از config پلتفرم خونده می‌شد،
     * یعنی برداشت متخصص‌های همه‌ی سالن‌ها از موجودی زرین‌پال پلتفرم پرداخت می‌شد.
     */
    public function isConfiguredFor(?Salon $salon): bool
    {
        return $salon !== null && $salon->canAutoPayout();
    }

    private function salonOf(WithdrawalRequest $withdrawalRequest): ?Salon
    {
        $salonId = Specialist::withoutGlobalScopes()->whereKey($withdrawalRequest->specialist_id)->value('salon_id');

        return $salonId ? Salon::withoutGlobalScopes()->find($salonId) : null;
    }

    public function payout(WithdrawalRequest $withdrawalRequest): array
    {
        $salon = $this->salonOf($withdrawalRequest);

        if (! $this->isConfiguredFor($salon)) {
            Log::warning('ZarinpalPayoutService: تسویه‌ی خودکار برای این سالن پیکربندی نشده', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'salon_id' => $salon?->id,
            ]);

            return [
                'success' => false,
                'message' => 'تسویه‌ی خودکار برای این سالن فعال نیست (کد پذیرنده یا توکن Payout زرین‌پال سالن وارد نشده). درخواست را دستی تسویه کنید.',
            ];
        }

        $this->merchantId = (string) $salon->zarinpal_merchant_id;
        $this->apiKey = (string) $salon->zarinpal_payout_api_key;

        $amount = (int) (($withdrawalRequest->net_amount ?? $withdrawalRequest->amount) * 10);

        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'description' => sprintf(
                'تسویه حساب متخصص: %s (درخواست #%d)',
                $withdrawalRequest->specialist?->name ?? '—',
                $withdrawalRequest->id
            ),
            'destination_iban' => $withdrawalRequest->iban,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl.'/payout.json', $payload);

            $result = $response->json();

            if ($response->successful() && (($result['data']['code'] ?? null) == 100)) {
                return [
                    'success' => true,
                    'reference_code' => $result['data']['payout_id'] ?? $result['data']['track_id'] ?? ('ZRP-'.$withdrawalRequest->id.'-'.now()->timestamp),
                    'payout_id' => $result['data']['payout_id'] ?? null,
                    'raw' => $result,
                ];
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $result['errors']['message'] ?? "خطای زرین‌پال (کد {$errorCode})";

            Log::error('ZarinpalPayoutService: درخواست Payout ناموفق', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error_code' => $errorCode,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('ZarinpalPayoutService: خطای اتصال به درگاه Payout', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه تسویه زرین‌پال. لطفاً بعداً دوباره تلاش کنید.',
            ];
        }
    }
}
