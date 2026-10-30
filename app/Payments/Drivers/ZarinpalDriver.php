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
 * ⭐ درگاه زرین‌پال (API v4) — منتقل‌شده از App\Services\PaymentService بدون تغییر رفتار (مرحله‌ی ۰،
 * ۲۰۲۶-۰۹-۲۴). همون endpointها، همون پارامترها، همون کدهای موفقیت (۱۰۰ / ۱۰۱) و همون متن خطاها.
 * credentials: ['merchant_id' => '...'].
 */
class ZarinpalDriver implements PaymentGatewayDriver
{
    /**
     * ⭐ بسته‌ی 5ز (۲۰۲۶-۱۰-۳۰): پاسخ verify با این کدها یعنی شاید پول از مشتری کسر شده باشد (‎-50 مبلغ ناهمخوان، ‎-52 خطای
     * غیرمنتظره‌ی زرین‌پال) ← raw['needs_attention'] و صفحه‌ی «پرداخت‌های نیازمند بررسی»، نه «ناموفق» بی‌صدا.
     */
    public const NEEDS_ATTENTION_CODES = [-50, -52];

    public const ATTENTION_NOTE = '. ممکن است مبلغ از حساب شما کسر شده باشد؛ این پرداخت برای بررسی دستی ثبت شد و اگر مبلغی کسر شده باشد، از بین نمی‌رود.';

    private string $apiUrl;

    private string $gatewayUrl;

    public function __construct(private readonly array $credentials, private readonly bool $sandbox)
    {
        if ($this->sandbox) {
            $this->apiUrl = 'https://sandbox.zarinpal.com/pg/v4/payment';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay';
        } else {
            // ⭐ ۲۰۲۶-۰۹-۲۵: طبق مستندات رسمی فعلی زرین‌پال (zarinpal.com/docs/paymentGateway/connectToGateway)
            // آدرس‌های production روی payment.zarinpal.com هستن — قبلاً کد api.zarinpal.com و
            // www.zarinpal.com/pg/StartPay رو صدا می‌زد. قابل override با env برای وقتی دوباره عوض بشن.
            $this->apiUrl = rtrim((string) config('services.zarinpal.payment_api_url', 'https://payment.zarinpal.com/pg/v4/payment'), '/');
            $this->gatewayUrl = rtrim((string) config('services.zarinpal.start_pay_url', 'https://payment.zarinpal.com/pg/StartPay'), '/');
        }
    }

    public function key(): string
    {
        return 'zarinpal';
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        // ⭐ بسته‌ی 5ز: حد مبلغ زرین‌پال پیش از درخواست — پیام روشن به‌جای ‎-9/‎-41؛ خطای منطقی است، درگاه بعدی امتحان نمی‌شود
        if ($violation = \App\Payments\GatewayAmountLimits::violation($request->amountRial)) {
            return GatewayStartResult::failed($violation, retryable: false, raw: ['amount_limit' => $request->amountRial]);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($this->apiUrl.'/request.json', [
                    'merchant_id' => (string) ($this->credentials['merchant_id'] ?? ''),
                    'amount' => $request->amountRial,
                    // ⭐ بسته‌ی 5ز: واحد صریح (پیش‌فرض در صفحه‌ی REST ذکر نشده؛ verify مبلغ را «به ریال» می‌گیرد)
                    'currency' => 'IRR',
                    'callback_url' => $request->callbackUrl,
                    'description' => $request->description,
                    'metadata' => [
                        'mobile' => $request->mobile ?? '',
                        'email' => $request->email ?? '',
                    ],
                ]);
        } catch (Throwable $e) {
            // پیام کاربر عمومی می‌ماند؛ علت واقعی (DNS، timeout، SSL، VPN) در لاگ «Payment Request Failed» می‌آید — قبلاً full_response خالی بود
            // و تشخیص ممکن نبود (گزارش ۲۰۲۶-۱۰-۲۱)
            return GatewayStartResult::failed('خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.', retryable: true, raw: [
                'connection_error' => class_basename($e).': '.mb_substr($e->getMessage(), 0, 300),
                'endpoint' => $this->apiUrl.'/request.json',
            ]);
        }

        $result = (array) $response->json();

        if ($response->successful() && (int) ($result['data']['code'] ?? 0) === 100) {
            $authority = (string) $result['data']['authority'];

            return GatewayStartResult::redirect($this->gatewayUrl.'/'.$authority, $authority, $result);
        }

        $code = self::errorCode($result);

        return GatewayStartResult::failed(self::errorMessage($code), retryable: $response->serverError(), raw: $result);
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $authority = $request->token ?? $request->callback['Authority'] ?? $request->callback['authority'] ?? null;
        $status = $request->callback['Status'] ?? $request->callback['status'] ?? null;

        if ($status === 'NOK' || $status === 'cancel') {
            return new GatewayVerifyResult(false, $authority, message: 'پرداخت توسط کاربر لغو شد', cancelledByUser: true);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($this->apiUrl.'/verify.json', [
                    'merchant_id' => (string) ($this->credentials['merchant_id'] ?? ''),
                    'authority' => $authority,
                    'amount' => $request->amountRial,
                ]);
        } catch (Throwable $e) {
            return \App\Payments\HttpFailure::neverSent($e)
                ? new GatewayVerifyResult(false, $authority, message: 'خطا در تایید پرداخت', raw: ['verify_not_sent' => true, 'connection_error' => class_basename($e).': '.mb_substr($e->getMessage(), 0, 300)])
                : new GatewayVerifyResult(false, $authority, message: 'پاسخ تایید از درگاه دریافت نشد. وضعیت پرداخت به‌صورت خودکار بررسی می‌شود و نتیجه با پیامک اطلاع داده می‌شود؛ اگر مبلغی کسر شده باشد، از بین نمی‌رود.', raw: ['unanswered' => true]);
        }

        // ⭐ ۵xx بعد از فرستادن: شاید درگاه تایید کرده باشه — payments:reconcile دوباره وضعیت رو می‌پرسه
        if ($response->serverError()) {
            return new GatewayVerifyResult(false, $authority, message: 'پاسخ تایید از درگاه دریافت نشد. وضعیت پرداخت به‌صورت خودکار بررسی می‌شود و نتیجه با پیامک اطلاع داده می‌شود؛ اگر مبلغی کسر شده باشد، از بین نمی‌رود.', raw: ['unanswered' => true, 'status' => $response->status()]);
        }

        $result = (array) $response->json();
        $code = self::errorCode($result);

        if ($response->successful() && in_array($code, [100, 101], true)) {
            return new GatewayVerifyResult(
                true,
                $authority,
                refId: (string) ($result['data']['ref_id'] ?? $authority),
                cardPan: $result['data']['card_pan'] ?? null,
                fee: isset($result['data']['fee']) ? (int) $result['data']['fee'] : null,
                raw: $result,
            );
        }

        if (in_array($code, self::NEEDS_ATTENTION_CODES, true)) {
            return new GatewayVerifyResult(false, $authority, message: self::errorMessage($code).self::ATTENTION_NOTE, raw: $result + ['needs_attention' => true]);
        }

        return new GatewayVerifyResult(false, $authority, message: self::errorMessage($code), raw: $result);
    }

    /**
     * ⭐ بسته‌ی 5ز (۲۰۲۶-۱۰-۳۰): کد خطا از هر دو شکل پاسخ مستند — `{"errors": {"message", "code", "validations"}}` (صفحه‌ی reverse)
     * و `{"message", "errors": {"authority": ["Invalid authority.", "-54"]}}` (صفحه‌ی inquiry)؛ موفق: `data.code`.
     */
    public static function errorCode(array $json): int
    {
        $data = $json['data'] ?? null;
        if (is_array($data) && isset($data['code']) && is_numeric($data['code'])) {
            return (int) $data['code'];
        }

        $errors = $json['errors'] ?? null;
        if (is_array($errors)) {
            if (isset($errors['code']) && is_numeric($errors['code'])) {
                return (int) $errors['code'];
            }
            foreach ($errors as $items) {
                foreach ((array) $items as $item) {
                    if (is_numeric($item)) {
                        return (int) $item;
                    }
                }
            }
        }

        return -999;
    }

    /**
     * ⭐ بسته‌ی 5ز: جدول رسمی v4 (`/paymentGateway/errorList.html`، تأییدشده با get_error_code در ۲۰۲۶-۱۰-۳۰) — عمومی ‎-9…‎-19 و 100،
     * درخواست ‎-40 ‎-41، تأیید ‎-50…‎-55 و 101، ریورس ‎-60…‎-63. جدول API قدیمی (‎-9 «IP»، ‎-10 «توکن»، ‎-11 «یافت نشد»، ‎-54 «آرشیو») کنار رفت.
     */
    public static function errorMessage(int $code): string
    {
        return match ($code) {
            -9 => 'خطای اعتبارسنجی درخواست پرداخت (کد پذیرنده، آدرس بازگشت، توضیحات، مبلغ یا کد معرف نامعتبر است)',
            -10 => 'آی‌پی یا کد پذیرنده (مرچنت) درگاه زرین‌پال صحیح نیست',
            -11 => 'کد پذیرنده فعال نیست؛ سالن باید با پشتیبانی زرین‌پال تماس بگیرد',
            -12 => 'تلاش بیش از حد در زمان کوتاه؛ چند دقیقه بعد دوباره تلاش کنید',
            -13 => 'سقف تراکنش درگاه پر شده است؛ سالن باید مدارک درگاه را نزد زرین‌پال تکمیل کند',
            -14 => 'آدرس بازگشت با دامنه‌ی ثبت‌شده‌ی درگاه زرین‌پال نمی‌خواند',
            -15 => 'درگاه پرداخت به حالت تعلیق درآمده است',
            -16 => 'سطح تأیید پذیرنده پایین‌تر از سطح نقره‌ای است',
            -17 => 'محدودیت پذیرنده در سطح آبی',
            -18 => 'ارجاع‌دهنده با دامنه‌ی ثبت‌شده‌ی درگاه نمی‌خواند (کد این درگاه برای این سایت نیست)',
            -19 => 'امکان ایجاد تراکنش برای این درگاه وجود ندارد',
            -40 => 'پارامتر اضافه‌ی درخواست نامعتبر است (expire_in)',
            -41 => 'حداکثر مبلغ پرداختی ۱۰۰ میلیون تومان است',
            -50 => 'مبلغ پرداخت‌شده با مبلغ تأیید متفاوت است',
            -51 => 'پرداخت ناموفق',
            -52 => 'خطای غیرمنتظره در زرین‌پال',
            -53 => 'پرداخت متعلق به این کد پذیرنده نیست',
            -54 => 'شناسه‌ی پرداخت (اتوریتی) نامعتبر است',
            -55 => 'تراکنش مورد نظر یافت نشد',
            -60 => 'امکان برگشت تراکنش با بانک وجود ندارد',
            -61 => 'تراکنش موفق نیست یا قبلاً برگشت خورده است',
            -62 => 'آی‌پی درگاه تنظیم نشده است',
            -63 => 'مهلت ۳۰ دقیقه‌ای برگشت تراکنش تمام شده است',
            100 => 'عملیات موفق',
            101 => 'تراکنش قبلاً تأیید شده است',
            default => 'خطای نامشخص درگاه پرداخت (کد: '.$code.')',
        };
    }
}
