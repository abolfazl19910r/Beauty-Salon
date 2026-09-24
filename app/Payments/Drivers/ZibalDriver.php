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
 * ⭐ درگاه زیبال (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — طبق مستندات رسمی help.zibal.ir/ipg:
 * - درخواست: POST https://gateway.zibal.ir/v1/request {merchant, amount (ریال), callbackUrl, description, orderId, mobile}
 *   → {result: 100, trackId}
 * - شروع: GET https://gateway.zibal.ir/start/{trackId}
 * - بازگشت: GET ?trackId&success&status&orderId
 * - تایید: POST /v1/verify {merchant, trackId} → result 100 (موفق) یا 201 (قبلاً تایید شده)؛ verify مبلغ
 *   نمی‌گیره، پس amount پاسخ با مبلغ تراکنش مقایسه می‌شه.
 * مرچنت تست رسمی: «zibal» (بدون پول واقعی، روی همون host).
 * credentials: ['merchant' => '...'].
 */
class ZibalDriver implements PaymentGatewayDriver
{
    public const BASE_URL = 'https://gateway.zibal.ir';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'zibal';
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->post(self::BASE_URL.'/v1/request', array_filter([
                'merchant' => (string) ($this->credentials['merchant'] ?? ''),
                'amount' => $request->amountRial,
                'callbackUrl' => $request->callbackUrl,
                'description' => mb_substr($request->description, 0, 250),
                'orderId' => $request->transactionId !== null ? (string) $request->transactionId : $request->orderId,
                'mobile' => $request->mobile ?: null,
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه زیبال. لطفاً دوباره تلاش کنید.', retryable: true);
        }

        $body = (array) $response->json();

        if ($response->successful() && (int) ($body['result'] ?? 0) === 100 && ! empty($body['trackId'])) {
            $trackId = (string) $body['trackId'];

            return GatewayStartResult::redirect(self::BASE_URL.'/start/'.$trackId, $trackId, $body);
        }

        return GatewayStartResult::failed(self::message((int) ($body['result'] ?? 0)), retryable: $response->serverError(), raw: $body);
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $trackId = $request->token ?? (isset($request->callback['trackId']) ? (string) $request->callback['trackId'] : null);

        if (($request->callback['success'] ?? null) !== null && (string) $request->callback['success'] !== '1') {
            $cancelled = (string) ($request->callback['status'] ?? '') === '3';

            return new GatewayVerifyResult(false, $trackId, message: $cancelled ? 'پرداخت توسط کاربر لغو شد' : 'پرداخت ناموفق بود', cancelledByUser: $cancelled);
        }

        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->post(self::BASE_URL.'/v1/verify', [
                'merchant' => (string) ($this->credentials['merchant'] ?? ''),
                'trackId' => is_numeric($trackId) ? (int) $trackId : $trackId,
            ]);
        } catch (Throwable) {
            return new GatewayVerifyResult(false, $trackId, message: 'خطا در تایید پرداخت');
        }

        $body = (array) $response->json();
        $result = (int) ($body['result'] ?? 0);

        if (! $response->successful() || ! in_array($result, [100, 201], true)) {
            return new GatewayVerifyResult(false, $trackId, message: self::message($result), raw: $body);
        }

        // verify زیبال مبلغ نمی‌گیره؛ اگه مبلغ پاسخ با مبلغ ثبت‌شده‌ی تراکنش یکی نباشه، پرداخت قبول نمی‌شه.
        if (isset($body['amount']) && (int) $body['amount'] !== $request->amountRial) {
            return new GatewayVerifyResult(false, $trackId, message: 'مبلغ پرداخت‌شده با مبلغ تراکنش همخوانی ندارد', raw: $body);
        }

        return new GatewayVerifyResult(
            true,
            $trackId,
            refId: isset($body['refNumber']) ? (string) $body['refNumber'] : $trackId,
            cardPan: $body['cardNumber'] ?? null,
            raw: $body,
        );
    }

    public static function message(int $code): string
    {
        return match ($code) {
            100 => 'با موفقیت تایید شد',
            102 => 'کد مرچنت زیبال یافت نشد',
            103 => 'مرچنت زیبال غیرفعال است یا قرارداد درگاه امضا نشده',
            104 => 'کد مرچنت زیبال نامعتبر است',
            105 => 'مبلغ باید بیشتر از ۱۰۰ تومان باشد',
            106 => 'آدرس بازگشت (callbackUrl) نامعتبر است',
            113 => 'مبلغ تراکنش از سقف مجاز بیشتر است',
            115 => 'IP سرور در پنل زیبال ثبت نشده است',
            201 => 'تراکنش قبلاً تایید شده است',
            202 => 'سفارش پرداخت نشده یا ناموفق بوده است',
            203 => 'شناسه‌ی پیگیری (trackId) نامعتبر است',
            default => 'خطای نامشخص در درگاه زیبال (کد: '.$code.')',
        };
    }
}
