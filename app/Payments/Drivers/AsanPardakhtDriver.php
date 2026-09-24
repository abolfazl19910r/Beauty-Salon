<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ⭐ درگاه آسان پرداخت — IPG REST نسخه‌ی ۱ (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵).
 *
 * مستند رسمی (IPG REST 1.9.3) فقط به پذیرنده تحویل داده می‌شه و صفحه‌ی عمومی نداره؛ این driver با
 * endpointها/فیلدهایی نوشته شده که در دو پیاده‌سازی نگهداری‌شده‌ی مستقل یکسان هستن (shetabit/multipay و
 * افزونه‌ی Pars Kit که صراحتاً به IPG REST 1.9.3 ارجاع می‌ده):
 * - زمان سرور: GET https://ipgrest.asanpardakht.ir/v1/Time → "yyyyMMdd HHmmss"
 * - توکن: POST /v1/Token {serviceTypeId: 1, merchantConfigurationId, localInvoiceId (عددی و یکتا), amountInRials,
 *   localDate, callbackURL, paymentId: "0", additionalData} با هدرهای usr / pwd → بدنه‌ی پاسخ خود توکن (RefId)
 * - انتقال: فرم POST خودکار به https://asan.shaparak.ir با RefId (+ mobileap)
 * - بازگشت: POST به callbackURL
 * - تایید سه‌مرحله‌ای: GET /v1/TranResult?merchantConfigurationId&localInvoiceId → payGateTranID؛
 *   POST /v1/Verify و بعد POST /v1/Settlement {merchantConfigurationId, payGateTranId}.
 * خطاها کد HTTP هستن (۴۷۱ تا ۴۹۷ و ۵۷۱ تا ۵۸۰) — کدهای ۵۷x خطای منطقی/پیکربندی‌ان نه قطعی، پس فقط
 * قطع اتصال یا ۵۰۰/۵۰۲/۵۰۳/۵۰۴ واقعی باعث جایگزینی خودکار درگاه می‌شه.
 * ⚠️ IP سرور باید در پنل آسان پرداخت ثبت بشه (خطای ۴۷۴).
 * credentials: ['merchant_config_id' => '...', 'username' => '...', 'password' => '...'].
 */
class AsanPardakhtDriver implements PaymentGatewayDriver
{
    public const API_URL = 'https://ipgrest.asanpardakht.ir/v1';

    public const PAYMENT_URL = 'https://asan.shaparak.ir';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'asanpardakht';
    }

    private function http()
    {
        return Http::timeout(30)->acceptJson()->asJson()->withHeaders([
            'usr' => (string) ($this->credentials['username'] ?? ''),
            'pwd' => (string) ($this->credentials['password'] ?? ''),
        ]);
    }

    private function configId(): int
    {
        return (int) ($this->credentials['merchant_config_id'] ?? 0);
    }

    private static function unavailable(Response $response): bool
    {
        return in_array($response->status(), [500, 502, 503, 504], true);
    }

    private function localDate(): string
    {
        try {
            $response = $this->http()->get(self::API_URL.'/Time');
            $time = trim((string) $response->body(), "\" \n\r\t");

            if ($response->successful() && preg_match('/^\d{8} \d{6}$/', $time)) {
                return $time;
            }
        } catch (Throwable) {
            // در ادامه با ساعت تهران ساخته می‌شه
        }

        return now('Asia/Tehran')->format('Ymd His');
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        $invoiceId = $request->transactionId ?? (int) (microtime(true) * 1000);

        try {
            $response = $this->http()->post(self::API_URL.'/Token', [
                'serviceTypeId' => 1,
                'merchantConfigurationId' => $this->configId(),
                'localInvoiceId' => $invoiceId,
                'amountInRials' => $request->amountRial,
                'localDate' => $this->localDate(),
                'callbackURL' => $request->callbackUrl,
                'paymentId' => '0',
                'additionalData' => mb_substr($request->description, 0, 100),
            ]);
        } catch (Throwable) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه آسان پرداخت. لطفاً دوباره تلاش کنید.', retryable: true);
        }

        $refId = trim((string) $response->body(), "\" \n\r\t");

        if ($response->status() === 200 && $refId !== '') {
            $fields = ['RefId' => $refId];
            if ($request->mobile) {
                $fields['mobileap'] = $request->mobile;
            }

            return GatewayStartResult::postForm(self::PAYMENT_URL, $refId, $fields, ['status' => 200, 'localInvoiceId' => $invoiceId]);
        }

        return GatewayStartResult::failed(self::message($response->status()), retryable: self::unavailable($response), raw: ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $token = $request->token;

        if ($request->transactionId === null) {
            return new GatewayVerifyResult(false, $token, message: 'شناسه‌ی تراکنش برای تایید آسان پرداخت در دسترس نیست');
        }

        try {
            $result = $this->http()->get(self::API_URL.'/TranResult', [
                'merchantConfigurationId' => $this->configId(),
                'localInvoiceId' => $request->transactionId,
            ]);
        } catch (Throwable) {
            return new GatewayVerifyResult(false, $token, message: 'خطا در تایید پرداخت');
        }

        if ($result->status() === 472) {
            return new GatewayVerifyResult(false, $token, message: 'پرداخت انجام نشد یا توسط کاربر لغو شد', cancelledByUser: true, raw: ['status' => 472]);
        }

        $tran = (array) $result->json();

        if ($result->status() !== 200 || empty($tran['payGateTranID'])) {
            return new GatewayVerifyResult(false, $token, message: self::message($result->status()), raw: ['status' => $result->status()] + $tran);
        }

        if (isset($tran['amount']) && (int) $tran['amount'] !== $request->amountRial) {
            return new GatewayVerifyResult(false, $token, message: 'مبلغ پرداخت‌شده با مبلغ تراکنش همخوانی ندارد', raw: $tran);
        }

        $payload = ['merchantConfigurationId' => $this->configId(), 'payGateTranId' => (int) $tran['payGateTranID']];

        try {
            $verify = $this->http()->post(self::API_URL.'/Verify', $payload);
            if ($verify->status() !== 200) {
                return new GatewayVerifyResult(false, $token, message: self::message($verify->status()), raw: $tran + ['verify_status' => $verify->status()]);
            }

            $settlement = $this->http()->post(self::API_URL.'/Settlement', $payload);
        } catch (Throwable) {
            return new GatewayVerifyResult(false, $token, message: 'خطا در تایید پرداخت');
        }

        return new GatewayVerifyResult(
            true,
            $token,
            refId: (string) ($tran['rrn'] ?? $tran['refID'] ?? $tran['payGateTranID']),
            cardPan: $tran['cardNumber'] ?? null,
            raw: $tran + ['settlement_status' => $settlement->status()],
        );
    }

    public static function message(int $status): string
    {
        return match ($status) {
            401 => 'نام کاربری/رمز وب‌سرویس آسان پرداخت ارسال نشده یا نامعتبر است',
            472 => 'تراکنش یافت نشد',
            473 => 'نام کاربری یا رمز عبور وب‌سرویس آسان پرداخت نادرست است',
            474 => 'IP سرور در پنل آسان پرداخت ثبت نشده است',
            486 => 'مبلغ در بازه‌ی مجاز نیست',
            487 => 'سرویس برای این پذیرنده فعال نیست',
            488 => 'آدرس بازگشت نامعتبر است',
            489 => 'شماره فاکتور تکراری است',
            490 => 'پذیرنده غیرفعال است یا پیکربندی آن ناقص است',
            571 => 'تراکنش هنوز پردازش نشده است',
            572 => 'وضعیت تراکنش نامشخص است',
            573 => 'امکان تایید تراکنش وجود ندارد',
            default => 'خطا در درگاه آسان پرداخت (کد: '.$status.')',
        };
    }
}
