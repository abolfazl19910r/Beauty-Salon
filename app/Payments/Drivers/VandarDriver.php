<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ⭐ درگاه وندار — IPG نسخه‌ی ۴ (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) طبق مستندات رسمی docs.vandar.io/ipg_service/ipg:
 * - ارسال: POST https://ipg.vandar.io/api/v4/send {api_key, amount (ریال، حداقل ۱۰۰۰), callback_url, mobile_number,
 *   factorNumber, description} → {status: 1, token}
 * - انتقال: GET https://ipg.vandar.io/v4/{token}
 * - بازگشت: ?token=...&payment_status=OK|FAILED
 * - تایید: POST /api/v4/verify {api_key, token} → status 1 (موفق)؛ ۲ = قبلاً تایید شده. پاسخ amount داره
 *   که با مبلغ تراکنش مقایسه می‌شه. تایید باید ظرف ۲۰ دقیقه انجام بشه.
 * ⚠️ callback_url و Referer باید روی دامنه‌ای باشن که در پنل وندار/شاپرک برای این درگاه ثبت شده.
 * credentials: ['api_key' => '...'].
 */
class VandarDriver implements PaymentGatewayDriver
{
    public const BASE_URL = 'https://ipg.vandar.io';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'vandar';
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->post(self::BASE_URL.'/api/v4/send', array_filter([
                'api_key' => (string) ($this->credentials['api_key'] ?? ''),
                'amount' => $request->amountRial,
                'callback_url' => $request->callbackUrl,
                'mobile_number' => $request->mobile ?: null,
                'factorNumber' => $request->transactionId !== null ? (string) $request->transactionId : $request->orderId,
                'description' => mb_substr($request->description, 0, 250),
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه وندار. لطفاً دوباره تلاش کنید.', retryable: true);
        }

        $body = (array) $response->json();

        if ($response->successful() && (int) ($body['status'] ?? 0) === 1 && ! empty($body['token'])) {
            $token = (string) $body['token'];

            return GatewayStartResult::redirect(self::BASE_URL.'/v4/'.$token, $token, $body);
        }

        return GatewayStartResult::failed(self::errors($body) ?? 'درخواست پرداخت توسط وندار پذیرفته نشد', retryable: $response->serverError(), raw: $body);
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $token = $request->token ?? (isset($request->callback['token']) ? (string) $request->callback['token'] : null);
        $paymentStatus = $request->callback['payment_status'] ?? null;

        if ($paymentStatus !== null && strtoupper((string) $paymentStatus) !== 'OK') {
            return new GatewayVerifyResult(false, $token, message: 'پرداخت انجام نشد یا توسط کاربر لغو شد', cancelledByUser: true);
        }

        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->post(self::BASE_URL.'/api/v4/verify', [
                'api_key' => (string) ($this->credentials['api_key'] ?? ''),
                'token' => $token,
            ]);
        } catch (Throwable) {
            return new GatewayVerifyResult(false, $token, message: 'خطا در تایید پرداخت');
        }

        $body = (array) $response->json();
        $status = (int) ($body['status'] ?? 0);

        if (! $response->successful() || ! in_array($status, [1, 2], true)) {
            return new GatewayVerifyResult(false, $token, message: self::errors($body) ?? 'تایید پرداخت توسط وندار ناموفق بود', raw: $body);
        }

        if (isset($body['amount']) && (int) round((float) $body['amount']) !== $request->amountRial) {
            return new GatewayVerifyResult(false, $token, message: 'مبلغ پرداخت‌شده با مبلغ تراکنش همخوانی ندارد', raw: $body);
        }

        return new GatewayVerifyResult(
            true,
            $token,
            refId: isset($body['transId']) ? (string) $body['transId'] : $token,
            cardPan: $body['cardNumber'] ?? null,
            raw: $body,
        );
    }

    /** وندار خطا رو به‌شکل آرایه‌ای از جمله‌های فارسی برمی‌گردونه (بدون کد عددی). */
    private static function errors(array $body): ?string
    {
        $errors = $body['errors'] ?? $body['message'] ?? null;

        if (is_array($errors)) {
            $errors = implode(' / ', array_filter(array_map(fn ($e) => is_scalar($e) ? (string) $e : null, $errors)));
        }

        return is_string($errors) && $errors !== '' ? mb_substr($errors, 0, 300) : null;
    }
}
