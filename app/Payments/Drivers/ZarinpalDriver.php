<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ⭐ درگاه زرین‌پال (API v4) — منتقل‌شده از App\Services\PaymentService بدون تغییر رفتار (مرحله‌ی ۰،
 * ۲۰۲۶-۰۹-۲۴). همون endpointها، همون پارامترها، همون کدهای موفقیت (۱۰۰ / ۱۰۱) و همون متن خطاها.
 * credentials: ['merchant_id' => '...'].
 */
class ZarinpalDriver implements PaymentGatewayDriver
{
    private string $apiUrl;

    private string $gatewayUrl;

    public function __construct(private readonly array $credentials, private readonly bool $sandbox)
    {
        if ($this->sandbox) {
            $this->apiUrl = 'https://sandbox.zarinpal.com/pg/v4/payment';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay';
        } else {
            $this->apiUrl = 'https://api.zarinpal.com/pg/v4/payment';
            $this->gatewayUrl = 'https://www.zarinpal.com/pg/StartPay';
        }
    }

    public function key(): string
    {
        return 'zarinpal';
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($this->apiUrl.'/request.json', [
                    'merchant_id' => (string) ($this->credentials['merchant_id'] ?? ''),
                    'amount' => $request->amountRial,
                    'callback_url' => $request->callbackUrl,
                    'description' => $request->description,
                    'metadata' => [
                        'mobile' => $request->mobile ?? '',
                        'email' => $request->email ?? '',
                    ],
                ]);
        } catch (ConnectionException $e) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.', retryable: true);
        } catch (Throwable $e) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.', retryable: true);
        }

        $result = (array) $response->json();

        if ($response->successful() && (int) ($result['data']['code'] ?? 0) === 100) {
            $authority = (string) $result['data']['authority'];

            return GatewayStartResult::redirect($this->gatewayUrl.'/'.$authority, $authority, $result);
        }

        $code = (int) ($result['data']['code'] ?? $result['errors']['code'] ?? -999);

        return GatewayStartResult::failed(self::errorMessage($code), retryable: $response->serverError(), raw: $result);
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $authority = $request->callback['Authority'] ?? $request->callback['authority'] ?? null;
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
            return new GatewayVerifyResult(false, $authority, message: 'خطا در تایید پرداخت');
        }

        $result = (array) $response->json();
        $code = (int) ($result['data']['code'] ?? $result['errors']['code'] ?? -999);

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

        return new GatewayVerifyResult(false, $authority, message: self::errorMessage($code), raw: $result);
    }

    public static function errorMessage(int $code): string
    {
        return match ($code) {
            -1 => 'اطلاعات ارسال شده ناقص است',
            -2 => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
            -3 => 'با توجه به محدودیت های شاپرک امکان پرداخت با رقم درخواست شده میسر نمی باشد',
            -4 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            -9 => 'آدرس IP درخواست کننده همگام با IP ثبت شده در وب سرویس نیست',
            -10 => 'توکن دسترسی غیرفعال شده است',
            -11 => 'درخواست مورد نظر یافت نشد',
            -12 => 'امکان ویرایش درخواست میسر نمی باشد',
            -15 => 'درگاه پرداخت به حالت تعلیق در آمده است',
            -16 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            -17 => 'محدودیت پذیرنده در وضعیت حاضر، امکان پرداخت را محدود می کند',
            -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
            -22 => 'تراکنش ناموفق می باشد',
            -30 => 'پذیرنده اجازه دسترسی به متد مربوطه را ندارد',
            -31 => 'حساب بانکی پذیرنده به درستی تعریف نشده است',
            -32 => 'مبلغ درخواستی از مبلغ کل تراکنش بیشتر است',
            -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد',
            -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
            -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد',
            -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر می باشد',
            -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز می باشد',
            -50 => 'مبلغ پرداخت شده با مقدار مبلغ در تراکنش همخوانی ندارد',
            -51 => 'پرداخت ناموفق بوده است',
            -52 => 'خطایی غیرمنتظره سرور رخ داده است. لطفا مشکل را به امور مشتریان زرین‌پال اطلاع دهید',
            -53 => 'پذیرنده در وب سرویس تسهیم شریک نیست',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            100 => 'عملیات با موفقیت انجام شد',
            101 => 'عملیات پرداخت موفق بوده و قبلاً PaymentVerification تراکنش انجام شده است',
            default => 'خطایی نامشخص در درگاه پرداخت (کد: '.$code.')',
        };
    }
}
