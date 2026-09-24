<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PayoutDriver;
use App\Payments\PayoutRequest;
use App\Payments\PayoutResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⭐ تسویه‌ی زیبال (مرحله‌ی ۳ چند درگاه، ۲۰۲۶-۰۹-۲۵) — طبق مستند رسمی پلتفرم زیبال (docs.zibal.ir/platform):
 * POST https://api.zibal.ir/v1/wallet/checkout با هدر Authorization: Bearer {ACCESS TOKEN}
 * (پنل زیبال ← حساب کاربری ← توسعه‌دهندگان ← API Tokenها؛ قابل محدود کردن به IP سرور)،
 * بدنه {amount, id (شناسه‌ی کیف پول), bankAccount (شبا), description} → {result: 1, data: {id}}.
 * checkoutDelay عمداً فرستاده نمی‌شه تا زیبال «در نزدیک‌ترین زمان ممکن» تسویه کنه.
 * ⚠️ واحد مبلغ: کل اکوسیستم زیبال (درگاه، موجودی کیف پول) ریاله و این driver ریال می‌فرسته، ولی مستند
 * این پایانه واحد رو صریحاً ننوشته — اولین تسویه‌ی واقعی با مبلغ کم انجام بشه.
 * credentials: ['payout_access_token' => '...', 'payout_wallet_id' => '...'].
 */
class ZibalPayoutDriver implements PayoutDriver
{
    public const URL = 'https://api.zibal.ir/v1/wallet/checkout';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'zibal';
    }

    public static function isConfigured(array $credentials): bool
    {
        return filled($credentials['payout_access_token'] ?? null) && filled($credentials['payout_wallet_id'] ?? null);
    }

    public function payout(PayoutRequest $request): PayoutResult
    {
        try {
            $response = Http::timeout(30)->acceptJson()->asJson()
                ->withToken((string) ($this->credentials['payout_access_token'] ?? ''))
                ->post(self::URL, [
                    'amount' => $request->amountRial,
                    'id' => (int) ($this->credentials['payout_wallet_id'] ?? 0),
                    'bankAccount' => $request->iban,
                    'description' => mb_substr($request->description, 0, 250),
                ]);
        } catch (Throwable $e) {
            Log::error('ZibalPayoutDriver: خطای اتصال', ['reference' => $request->reference, 'error' => $e->getMessage()]);

            return new PayoutResult(false, message: 'خطا در اتصال به سرویس تسویه‌ی زیبال. لطفاً بعداً دوباره تلاش کنید.');
        }

        $body = (array) $response->json();

        if ($response->successful() && (int) ($body['result'] ?? 0) === 1 && ! empty($body['data']['id'])) {
            return new PayoutResult(true, referenceCode: (string) $body['data']['id'], payoutId: (string) $body['data']['id'], raw: $body);
        }

        Log::error('ZibalPayoutDriver: درخواست تسویه ناموفق', ['reference' => $request->reference, 'status' => $response->status(), 'response' => $body]);

        $message = match (true) {
            $response->status() === 401 => 'توکن دسترسی زیبال نامعتبر است یا مجوز تسویه ندارد',
            $response->status() === 403 => 'این توکن زیبال اجازه‌ی تسویه ندارد یا IP سرور برایش مجاز نیست',
            default => $body['message'] ?? 'درخواست تسویه توسط زیبال پذیرفته نشد (کد '.($body['result'] ?? $response->status()).')',
        };

        return new PayoutResult(false, message: $message, raw: $body);
    }
}
