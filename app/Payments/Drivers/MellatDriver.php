<?php

namespace App\Payments\Drivers;

use App\Models\PaymentTransaction;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\Contracts\ReversibleGateway;
use App\Payments\GatewayReceipt;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use DOMDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⭐ درگاه مستقیم بانک ملت — به‌پرداخت ملت (وب‌سرویس SOAP)، مرحله‌ی ۲ چند درگاه.
 *
 * طبق راهنمای رسمی به‌پرداخت «How to integrate with Behpardakht Payment Gateway» (نگارش ۱٫۱، اردیبهشت ۱۳۹۲ —
 * آخرین نگارش عمومی؛ نسخه‌های بعدی فقط به پذیرنده داده می‌شن) و پیاده‌سازی‌های نگهداری‌شده‌ای که همین
 * endpointها رو امروز هم استفاده می‌کنن (Parbad، shetabit/multipay، افزونه‌ی Pars Kit):
 * - وب‌سرویس: POST https://bpm.shaparak.ir/pgwchannel/services/pgw (SOAP 1.1، namespace
 *   http://interfaces.core.sw.bps.com/). درخواست XML خام با Http فرستاده می‌شه نه ext-soap: WSDL سخت‌گیره
 *   (المنت ناشناخته = Unmarshalling Error)، و این‌طوری هیچ وابستگی به ext-soap / دانلود WSDL در هر درخواست نیست.
 * - شروع: bpPayRequest {terminalId, userName, userPassword, orderId (عدد یکتا در کل عمر ترمینال — id تراکنش ما),
 *   amount (ریال), localDate (yyyyMMdd), localTime (HHmmss), additionalData, callBackUrl, payerId=0}
 *   → "0,RefId" یا کد خطا. انتقال: فرم POST با RefId به …/pgwchannel/startpay.mellat.
 * - بازگشت: POST به callBackUrl با RefId / ResCode / SaleOrderId / SaleReferenceId / CardHolderInfo
 *   (+ CardHolderPan و FinalAmount در نسخه‌های جدیدتر). ResCode ۰ یعنی موفق، ۱۷ یعنی انصراف مشتری.
 * - تایید: bpVerifyRequest {orderId, saleOrderId, saleReferenceId} → "0". ⚠️ باید ظرف ۱۵ دقیقه باشه، وگرنه
 *   خود به‌پرداخت برگشت می‌زنه. ۴۳ = قبلاً verify شده.
 * - واریز: bpSettleRequest با همون پارامترها → "0" (یا ۴۵ = قبلاً settle شده). بدون settle پول به حساب
 *   پذیرنده نمی‌رسه.
 * - برگشت: bpReversalRequest — فقط برای تراکنشِ settle‌نشده، تا ۲ ساعت بعد از پرداخت. ۴۸ = قبلاً برگشت خورده.
 *
 * ⚠️ امنیت بازگشت: پاسخ verify هیچ مبلغی برنمی‌گردونه — مبلغ رو خود بانک به orderId ای که ما در bpPayRequest
 * فرستادیم گره زده و verify جفت (saleOrderId, saleReferenceId) رو روی ترمینال ما چک می‌کنه. پس قبل از verify:
 * RefId بازگشت = توکن ذخیره‌شده‌ی همین تراکنش، SaleOrderId = id همین تراکنش، و verify همیشه با id خودمون (نه
 * مقدار callback). SaleReferenceId هم با GatewayReceipt به همین تراکنش قفل می‌شه (مثل سامان) تا callbackهای
 * هم‌زمان و reconcile همدیگه رو ببینن. FinalAmount (اگه اومد) فقط ذخیره می‌شه — معنای دقیقش در مستند عمومی نیست.
 *
 * ⚠️ مسیرهای ناتمام (تصمیم ۲۰۲۶-۰۹-۲۶: پول مشتری هیچ‌وقت بدون خدمت نمی‌مونه):
 * - پاسخ verify نرسید → unanswered=true → payments:reconcile بعد از بسته شدن مهلت ۱۵ دقیقه‌ای verify برگشت می‌زنه.
 *   تا اون موقع refresh صفحه‌ی مشتری دوباره verify می‌کنه (۴۳ قبوله چون رسید قبلاً مال همین تراکنش شده).
 * - verify شد ولی settle نشد (خطا یا بی‌پاسخ) → همون لحظه bpReversalRequest: ۰/۴۸ → رد + reversed=true (پیامک
 *   settle_failed)؛ اگه جواب ۴۵ بود یعنی settle در واقع انجام شده بود → پرداخت موفقه؛ اگه برگشت هم جواب نداد →
 *   unanswered=true برای reconcile.
 *
 * credentials: ['terminal_id' => '...', 'username' => '...', 'password' => '...'] — IP سرور باید نزد به‌پرداخت ثبت
 * شده باشه (کد ۴۲۱) و callBackUrl روی دامنه‌ی ثبت‌شده (کد ۶۲).
 */
class MellatDriver implements PaymentGatewayDriver, ReversibleGateway
{
    public const SERVICE_URL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw';

    public const PAYMENT_URL = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

    public const SOAP_NAMESPACE = 'http://interfaces.core.sw.bps.com/';

    /** «سیستم بانک در دسترس نیست» — مثل قطعی شبکه، اجازه‌ی رفتن سراغ درگاه بعدی سالن رو می‌ده. */
    private const UNAVAILABLE_CODES = ['34', '113'];

    private const UNANSWERED_MESSAGE = 'پاسخ تایید از بانک ملت دریافت نشد. اگر مبلغی کسر شده باشد، به حساب شما برمی‌گردد.';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'mellat';
    }

    private function auth(): array
    {
        return [
            'terminalId' => (string) ($this->credentials['terminal_id'] ?? ''),
            'userName' => (string) ($this->credentials['username'] ?? ''),
            'userPassword' => (string) ($this->credentials['password'] ?? ''),
        ];
    }

    public static function envelope(string $method, array $params): string
    {
        $body = '';
        foreach ($params as $name => $value) {
            $body .= '<'.$name.'>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$name.'>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:int="'.self::SOAP_NAMESPACE.'">'
            .'<soapenv:Header/><soapenv:Body><int:'.$method.'>'.$body.'</int:'.$method.'></soapenv:Body></soapenv:Envelope>';
    }

    /**
     * یک فراخوانی وب‌سرویس.
     *
     * answered=false یعنی معلوم نیست بانک درخواست رو پردازش کرده یا نه (قطع اتصال، ۵xx بدون Fault).
     *
     * @return array{answered: bool, return: ?string, fault: ?string, status: ?int}
     */
    private function call(string $method, array $params, int $tries = 1): array
    {
        try {
            $response = Http::timeout(30)
                ->when($tries > 1, fn ($http) => $http->retry($tries, 500, fn ($e) => $e instanceof ConnectionException, throw: false))
                ->withBody(self::envelope($method, $this->auth() + $params), 'text/xml; charset=utf-8')
                ->post(self::SERVICE_URL);
        } catch (Throwable) {
            return ['answered' => false, 'return' => null, 'fault' => null, 'status' => null];
        }

        $xml = (string) $response->body();
        $return = self::node($xml, 'return');
        $fault = self::node($xml, 'faultstring');

        if ($return === null && $fault === null && ($response->serverError() || $xml === '')) {
            return ['answered' => false, 'return' => null, 'fault' => null, 'status' => $response->status()];
        }

        return ['answered' => true, 'return' => $return, 'fault' => $fault, 'status' => $response->status()];
    }

    /** مقدار اولین المنت با این نام محلی (مستقل از prefix)؛ null اگه XML نامعتبر یا المنت نبود. */
    private static function node(string $xml, string $localName): ?string
    {
        if (trim($xml) === '') {
            return null;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $nodes = $dom->getElementsByTagNameNS('*', $localName);
        if ($nodes->length === 0) {
            $nodes = $dom->getElementsByTagName($localName);
        }

        return $nodes->length > 0 ? trim((string) $nodes->item(0)->textContent) : null;
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        $orderId = (string) ($request->transactionId ?? (int) (microtime(true) * 1000));
        $now = now('Asia/Tehran');

        $result = $this->call('bpPayRequest', [
            'orderId' => $orderId,
            'amount' => $request->amountRial,
            'localDate' => $now->format('Ymd'),
            'localTime' => $now->format('His'),
            'additionalData' => mb_substr($request->description, 0, 200),
            'callBackUrl' => $request->callbackUrl,
            'payerId' => 0,
        ]);

        if (! $result['answered']) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه بانک ملت. لطفاً دوباره تلاش کنید.', retryable: true, raw: ['status' => $result['status']]);
        }

        $parts = array_map('trim', explode(',', (string) $result['return'], 2));
        $code = $parts[0];
        $refId = $parts[1] ?? '';

        if ($code === '0' && $refId !== '') {
            return GatewayStartResult::postForm(self::PAYMENT_URL, $refId, ['RefId' => $refId], ['ResCode' => 0, 'orderId' => $orderId]);
        }

        if ($result['return'] === null) {
            return GatewayStartResult::failed('پاسخ نامعتبر از وب‌سرویس بانک ملت', raw: ['fault' => $result['fault'], 'status' => $result['status']]);
        }

        return GatewayStartResult::failed(
            self::message($code),
            retryable: in_array($code, self::UNAVAILABLE_CODES, true),
            raw: ['ResCode' => $code, 'orderId' => $orderId],
        );
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $callback = $request->callback;
        $token = $request->token ?? (isset($callback['RefId']) ? (string) $callback['RefId'] : null);
        $fail = fn (string $message, array $raw = [], bool $cancelled = false) => new GatewayVerifyResult(false, $token, message: $message, cancelledByUser: $cancelled, raw: $raw);

        if ($request->transactionId === null) {
            return $fail('شناسه‌ی تراکنش برای تایید پرداخت بانک ملت در دسترس نیست');
        }

        // بازگشت باید مال همین تراکنش باشه (نه پارامترهای یک پرداخت دیگه)
        if (isset($callback['RefId'], $request->token) && ! hash_equals($request->token, (string) $callback['RefId'])) {
            return $fail('اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد');
        }
        if (isset($callback['SaleOrderId']) && (string) $callback['SaleOrderId'] !== (string) $request->transactionId) {
            return $fail('اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد');
        }

        $resCode = trim((string) ($callback['ResCode'] ?? ''));
        if ($resCode === '') {
            return $fail('نتیجه‌ی پرداخت از بانک ملت دریافت نشد');
        }
        if ($resCode !== '0') {
            return $fail(self::message($resCode), ['ResCode' => $resCode], cancelled: $resCode === '17');
        }

        $saleReferenceId = trim((string) ($callback['SaleReferenceId'] ?? ''));
        if (! ctype_digit($saleReferenceId)) {
            return $fail('شماره‌ی مرجع پرداخت از بانک ملت دریافت نشد');
        }

        // قبل از verify: هر رسید فقط برای یک تراکنش (و callbackهای هم‌زمان همین تراکنش همدیگه رو می‌بینن)
        if (! GatewayReceipt::claim($this->key(), $saleReferenceId, $request->transactionId)) {
            return $fail('این رسید پرداخت قبلاً برای تراکنش دیگری استفاده شده است', ['SaleReferenceId' => $saleReferenceId]);
        }

        $ids = self::ids($request->transactionId, $saleReferenceId);
        $raw = array_filter([
            'SaleReferenceId' => $saleReferenceId,
            'SaleOrderId' => (string) $request->transactionId,
            'CardHolderPan' => $callback['CardHolderPan'] ?? null,
            'FinalAmount' => $callback['FinalAmount'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $verify = $this->call('bpVerifyRequest', $ids, tries: 3);
        if (! $verify['answered']) {
            return $fail(self::UNANSWERED_MESSAGE, $raw + ['unanswered' => true]);
        }

        $verifyCode = trim((string) $verify['return']);
        $raw['verify'] = $verifyCode;
        if (! in_array($verifyCode, ['0', '43'], true)) {
            return $fail($verify['return'] === null ? 'پاسخ نامعتبر از بانک ملت در تایید پرداخت' : self::message($verifyCode), $raw);
        }

        $settle = $this->call('bpSettleRequest', $ids, tries: 3);
        $settleCode = $settle['answered'] ? trim((string) $settle['return']) : null;
        $raw['settle'] = $settleCode;

        if (! in_array($settleCode, ['0', '45'], true)) {
            // verify شده ولی پول به حساب سالن نرسید — تا settle نشده می‌شه کامل برگردوند
            $reversal = $this->reversal($request->transactionId, $saleReferenceId);
            $raw['reversal'] = $reversal;

            if ($reversal === '45') {
                $settleCode = '45'; // settle در واقع انجام شده بود و فقط پاسخش نرسیده بود
                $raw['settle'] = '45';
            } elseif (in_array($reversal, ['0', '48'], true)) {
                return $fail(
                    'تایید نهایی پرداخت در بانک ملت انجام نشد؛ کل مبلغ به حساب شما برگشت داده شد',
                    $raw + ['reversed' => true, 'refund_reason' => 'settle_failed'],
                );
            } else {
                return $fail(self::UNANSWERED_MESSAGE, $raw + ['unanswered' => true]);
            }
        }

        return new GatewayVerifyResult(
            true,
            $token,
            refId: $saleReferenceId,
            cardPan: isset($callback['CardHolderPan']) && $callback['CardHolderPan'] !== '' ? (string) $callback['CardHolderPan'] : null,
            raw: $raw + ['settled' => true],
        );
    }

    private static function ids(int|string $orderId, string $saleReferenceId): array
    {
        return ['orderId' => (string) $orderId, 'saleOrderId' => (string) $orderId, 'saleReferenceId' => $saleReferenceId];
    }

    /** bpReversalRequest؛ کد پاسخ، یا null اگه جوابی نرسید. */
    private function reversal(int|string $orderId, string $saleReferenceId): ?string
    {
        $result = $this->call('bpReversalRequest', self::ids($orderId, $saleReferenceId), tries: 3);

        return $result['answered'] && $result['return'] !== null ? trim($result['return']) : null;
    }

    /**
     * برگشت کل مبلغ به کارت. فقط تراکنشِ settle‌نشده (ملت تراکنش settle‌شده رو برنمی‌گردونه — مسیر کیف پول).
     */
    public function reverseTransaction(PaymentTransaction $transaction): bool
    {
        $receipt = (string) $transaction->gateway_receipt;

        if ($transaction->driver !== $this->key() || ! ctype_digit($receipt) || (((array) $transaction->verify_response)['settled'] ?? false) === true) {
            return false;
        }

        $code = $this->reversal($transaction->id, $receipt);

        if ($code === '45') {
            Log::error('Mellat payment is settled but recorded as not paid — needs a manual check', [
                'transaction_id' => $transaction->id, 'salon_id' => $transaction->salon_id, 'sale_reference_id' => $receipt,
            ]);
        }

        return in_array($code, ['0', '48'], true);
    }

    public static function message(string $code): string
    {
        return match ($code) {
            '11' => 'شماره کارت نامعتبر است',
            '12' => 'موجودی کارت کافی نیست',
            '13' => 'رمز کارت نادرست است',
            '14' => 'تعداد دفعات ورود رمز بیش از حد مجاز است',
            '15' => 'کارت نامعتبر است',
            '16' => 'دفعات برداشت از کارت بیش از حد مجاز است',
            '17' => 'پرداخت توسط کاربر لغو شد',
            '18' => 'تاریخ انقضای کارت گذشته است',
            '19' => 'مبلغ برداشت بیش از حد مجاز کارت است',
            '111' => 'صادرکننده‌ی کارت نامعتبر است',
            '112' => 'خطای سوئیچ صادرکننده‌ی کارت',
            '113' => 'پاسخی از صادرکننده‌ی کارت دریافت نشد',
            '114' => 'دارنده‌ی کارت مجاز به این تراکنش نیست',
            '21' => 'پذیرنده (ترمینال بانک ملت) نامعتبر است',
            '23' => 'خطای امنیتی در درگاه بانک ملت',
            '24' => 'نام کاربری یا رمز ترمینال بانک ملت نادرست است',
            '25' => 'مبلغ نامعتبر است',
            '31' => 'پاسخ نامعتبر از درگاه بانک ملت',
            '32' => 'قالب اطلاعات ارسالی به بانک ملت نادرست است',
            '33' => 'حساب نامعتبر است',
            '34' => 'خطای سیستمی بانک ملت',
            '35' => 'تاریخ نامعتبر است',
            '41' => 'شماره‌ی سفارش تکراری است',
            '42' => 'تراکنش خرید در بانک ملت یافت نشد',
            '43' => 'این تراکنش قبلاً تایید شده است',
            '44' => 'درخواست تایید در بانک ملت یافت نشد',
            '45' => 'تراکنش قبلاً واریز (Settle) شده است',
            '46' => 'تراکنش واریز (Settle) نشده است',
            '47' => 'تراکنش واریز در بانک ملت یافت نشد',
            '48' => 'تراکنش برگشت خورده است',
            '49' => 'تراکنش بازپرداخت یافت نشد',
            '415' => 'زمان پرداخت در درگاه بانک ملت به پایان رسید',
            '417' => 'شناسه‌ی پرداخت‌کننده نامعتبر است',
            '421' => 'IP سرور نزد به‌پرداخت ملت ثبت نشده است',
            '51' => 'تراکنش تکراری است',
            '54' => 'تراکنش مرجع یافت نشد',
            '55' => 'تراکنش نامعتبر است',
            '61' => 'خطا در واریز به حساب پذیرنده',
            '62' => 'آدرس بازگشت در دامنه‌ی ثبت‌شده نزد به‌پرداخت نیست',
            default => 'خطا در درگاه بانک ملت (کد: '.$code.')',
        };
    }
}
