<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PayoutDriver;
use App\Payments\HttpFailure;
use App\Payments\PayoutRequest;
use App\Payments\PayoutResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⭐ تسویه‌ی زرین‌پال (Payout API) — همون منطق SalonPayoutService قبلی (۲۰۲۶-۰۹-۲۴)، حالا روی لایه‌ی
 * درگاه‌ها و با اطلاعات ردیف zarinpal خود سالن: credentials['merchant_id'] + credentials['payout_api_key'].
 * ⚠️ sandbox زرین‌پال برای Payout معمولاً همیشه پاسخ موفق شبیه‌سازی‌شده می‌ده؛ فقط مسیر HTTP و parse پاسخ
 * رو تست می‌کنه، نه جابه‌جایی واقعی پول. تست نهایی با مبلغ کم روی production.
 */
class ZarinpalPayoutDriver implements PayoutDriver
{
    public function __construct(private readonly array $credentials, private readonly bool $sandbox) {}

    public function key(): string
    {
        return 'zarinpal';
    }

    public static function isConfigured(array $credentials): bool
    {
        return filled($credentials['merchant_id'] ?? null) && filled($credentials['payout_api_key'] ?? null);
    }

    public function payout(PayoutRequest $request): PayoutResult
    {
        $url = ($this->sandbox ? config('services.zarinpal.payout.sandbox_base_url') : config('services.zarinpal.payout.base_url')).'/payout.json';

        try {
            $response = Http::timeout(30)->acceptJson()->asJson()
                ->withToken((string) ($this->credentials['payout_api_key'] ?? ''))
                ->post($url, [
                    'merchant_id' => (string) ($this->credentials['merchant_id'] ?? ''),
                    'amount' => $request->amountRial,
                    'description' => $request->description,
                    'destination_iban' => $request->iban,
                ]);
        } catch (Throwable $e) {
            Log::error('ZarinpalPayoutDriver: خطای اتصال', ['reference' => $request->reference, 'error' => $e->getMessage()]);

            return HttpFailure::neverSent($e)
                ? new PayoutResult(false, message: 'خطا در اتصال به درگاه تسویه زرین‌پال. لطفاً بعداً دوباره تلاش کنید.')
                : self::unknown($request, null);
        }

        if ($response->serverError()) {
            return self::unknown($request, $response->status());
        }

        $result = (array) $response->json();

        if ($response->successful() && (($result['data']['code'] ?? null) == 100)) {
            $payoutId = $result['data']['payout_id'] ?? null;

            return new PayoutResult(
                true,
                referenceCode: (string) ($payoutId ?? $result['data']['track_id'] ?? ('ZRP-'.$request->reference.'-'.now()->timestamp)),
                payoutId: $payoutId !== null ? (string) $payoutId : null,
                raw: $result,
            );
        }

        $code = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
        Log::error('ZarinpalPayoutDriver: درخواست Payout ناموفق', ['reference' => $request->reference, 'error_code' => $code, 'response' => $result]);

        return new PayoutResult(false, message: $result['errors']['message'] ?? "خطای زرین‌پال (کد {$code})", raw: $result);
    }

    /** ⭐ درخواست فرستاده شد ولی پاسخ نرسید: شاید زرین‌پال واریز کرده باشه — ProcessWithdrawalJob برای بررسی دستی نگه می‌داره. */
    private static function unknown(PayoutRequest $request, ?int $status): PayoutResult
    {
        Log::error('ZarinpalPayoutDriver: نتیجه‌ی تسویه نامعلوم — بررسی دستی در پنل زرین‌پال', ['reference' => $request->reference, 'status' => $status]);

        return new PayoutResult(
            false,
            message: 'پاسخ زرین‌پال دریافت نشد؛ ممکن است تسویه انجام شده باشد. واریزهای اخیر را در پنل زرین‌پال بررسی کنید (برداشت #'.$request->reference.').',
            raw: ['status' => $status],
            unknown: true,
        );
    }
}
