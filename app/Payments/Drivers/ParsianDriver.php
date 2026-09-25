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
 * ⭐ درگاه مستقیم بانک پارسیان — تجارت الکترونیک پارسیان (PEC، وب‌سرویس ASMX/SOAP)، آخرین درگاه مرحله‌ی ۲.
 *
 * منابع (۲۰۲۶-۰۹-۲۶): صفحه‌ی راهنمای خودِ وب‌سرویس زنده (SaleService.asmx?op=SalePaymentRequest: SOAP 1.1،
 * namespace https://pec.Shaparak.ir/NewIPGServices/Sale/SaleService، SOAPAction «{ns}/SalePaymentRequest»، المنت
 * requestData) و سه پیاده‌سازی نگهداری‌شده با همین endpointها (Parbad، shetabit/multipay، pec_ir). مستند PDF رسمی
 * عمومی در دسترس نبود.
 * - شروع: SalePaymentRequest {LoginAccount (PIN), Amount (ریال), OrderId (عدد یکتا — id تراکنش ما), CallBackUrl,
 *   AdditionalData} → {Token, Status, Message}؛ Status ۰ و Token > ۰ → هدایت GET به https://pec.shaparak.ir/NewIPG/?Token=…
 * - بازگشت: POST به CallBackUrl با Token / status / OrderId / Amount / RRN / TerminalNo / HashCardNumber (بزرگی و کوچکی
 *   حروف کلیدها در پیاده‌سازی‌ها متفاوت است، پس بدون حساسیت خونده می‌شن). status ۰ = موفق، ‎-138 = انصراف مشتری.
 * - تایید: ConfirmService.asmx → ConfirmPayment {LoginAccount, Token} → {Status, RRN, CardNumberMasked, Token}.
 *   ‎-1533 / 2 = قبلاً تایید شده. پیام صفحه‌ی خود بانک: تراکنش تاییدنشده حدود ۶۰ دقیقه بعد به مشتری برمی‌گرده.
 * - برگشت: ReversalService.asmx (مسیر Reverse/) → ReversalRequest {LoginAccount, Token}؛ ۰ یا ‎-1551 (قبلاً برگشت
 *   خورده) = موفق، ‎-1549 = مهلت برگشت تمام شده (مهلت دقیق در منبع عمومی نیست). ⚠️ namespace این سرویس
 *   (…/Reversal/ReversalService، SOAP 1.2) از Parbad گرفته شده و تنها بخشیه که از صفحه‌ی زنده تأیید نشد؛ اگر
 *   اشتباه باشه برگشت false می‌ده و مسیرهای جایگزین (reconcile بعدی / کیف پول) اجرا می‌شن.
 *
 * ⚠️ امنیت: تایید همیشه با توکنِ ذخیره‌شده‌ی خودمون (نه مقدار callback) — بانک توکن رو به OrderId و مبلغی که ما
 * فرستادیم گره زده. قبل از تایید، Token / OrderId / Amount بازگشت (و TerminalNo اگر در تنظیمات هست) باید با همین
 * تراکنش بخونن، و رسید (توکن) با GatewayReceipt به همین تراکنش قفل می‌شه.
 * برخلاف ملت، پارسیان تراکنشِ تاییدشده رو برمی‌گردونه — پس «ساعت نوبت از دست رفت» به کارت برمی‌گرده.
 *
 * credentials: ['pin' => '...', 'terminal_id' => '...'(اختیاری)] — IP سرور باید نزد پارسیان ثبت شده باشه.
 */
class ParsianDriver implements PaymentGatewayDriver, ReversibleGateway
{
    public const SALE_URL = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx';

    public const CONFIRM_URL = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx';

    public const REVERSAL_URL = 'https://pec.shaparak.ir/NewIPGServices/Reverse/ReversalService.asmx';

    public const PAYMENT_URL = 'https://pec.shaparak.ir/NewIPG/';

    public const SALE_NS = 'https://pec.Shaparak.ir/NewIPGServices/Sale/SaleService';

    public const CONFIRM_NS = 'https://pec.Shaparak.ir/NewIPGServices/Confirm/ConfirmService';

    public const REVERSAL_NS = 'https://pec.Shaparak.ir/NewIPGServices/Reversal/ReversalService';

    private const UNANSWERED_MESSAGE = 'پاسخ تایید از بانک پارسیان دریافت نشد. اگر مبلغی کسر شده باشد، به حساب شما برمی‌گردد.';

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'parsian';
    }

    private function pin(): string
    {
        return (string) ($this->credentials['pin'] ?? '');
    }

    /** بدنه‌ی SOAP؛ $soap12 فقط برای سرویس برگشت (مثل Parbad). */
    public static function envelope(string $namespace, string $method, array $fields, bool $soap12 = false): string
    {
        $data = '';
        foreach ($fields as $name => $value) {
            $data .= '<'.$name.'>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$name.'>';
        }
        $env = $soap12 ? 'http://www.w3.org/2003/05/soap-envelope' : 'http://schemas.xmlsoap.org/soap/envelope/';

        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:soap="'.$env.'"><soap:Body>'
            .'<'.$method.' xmlns="'.$namespace.'"><requestData>'.$data.'</requestData></'.$method.'>'
            .'</soap:Body></soap:Envelope>';
    }

    /**
     * answered=false یعنی معلوم نیست بانک پردازش کرده یا نه (قطع اتصال، ۵xx بدون Fault).
     *
     * @return array{answered: bool, fields: array<string, string>, fault: ?string, status: ?int}
     */
    private function call(string $url, string $namespace, string $method, array $fields, int $tries = 1, bool $soap12 = false): array
    {
        $body = self::envelope($namespace, $method, ['LoginAccount' => $this->pin()] + $fields, $soap12);
        $contentType = $soap12
            ? 'application/soap+xml; charset=utf-8; action="'.$namespace.'/'.$method.'"'
            : 'text/xml; charset=utf-8';

        try {
            $response = Http::timeout(30)
                ->when(! $soap12, fn ($http) => $http->withHeaders(['SOAPAction' => '"'.$namespace.'/'.$method.'"']))
                ->when($tries > 1, fn ($http) => $http->retry($tries, 500, fn ($e) => $e instanceof ConnectionException, throw: false))
                ->withBody($body, $contentType)
                ->post($url);
        } catch (Throwable) {
            return ['answered' => false, 'fields' => [], 'fault' => null, 'status' => null];
        }

        [$result, $fault] = self::parse((string) $response->body(), $method.'Result');

        if ($result === null && $fault === null && ($response->serverError() || trim((string) $response->body()) === '')) {
            return ['answered' => false, 'fields' => [], 'fault' => null, 'status' => $response->status()];
        }

        return ['answered' => true, 'fields' => $result ?? [], 'fault' => $fault, 'status' => $response->status()];
    }

    /** @return array{0: ?array<string, string>, 1: ?string} فیلدهای المنت نتیجه، و faultstring */
    private static function parse(string $xml, string $resultElement): array
    {
        if (trim($xml) === '') {
            return [null, null];
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return [null, null];
        }

        $fault = null;
        foreach (['faultstring', 'Text'] as $name) {
            $node = $dom->getElementsByTagNameNS('*', $name)->item(0) ?? $dom->getElementsByTagName($name)->item(0);
            if ($node && $dom->getElementsByTagNameNS('*', 'Fault')->length > 0) {
                $fault = trim($node->textContent);
                break;
            }
        }

        $result = $dom->getElementsByTagNameNS('*', $resultElement)->item(0);
        if (! $result) {
            return [null, $fault];
        }

        $fields = [];
        foreach ($result->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $fields[$child->localName] = trim($child->textContent);
            }
        }

        return [$fields, $fault];
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        $orderId = (string) ($request->transactionId ?? (int) (microtime(true) * 1000));

        $result = $this->call(self::SALE_URL, self::SALE_NS, 'SalePaymentRequest', [
            'Amount' => $request->amountRial,
            'OrderId' => $orderId,
            'CallBackUrl' => $request->callbackUrl,
            'AdditionalData' => mb_substr($request->description, 0, 200),
        ]);

        if (! $result['answered']) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه بانک پارسیان. لطفاً دوباره تلاش کنید.', retryable: true, raw: ['status' => $result['status']]);
        }

        $status = $result['fields']['Status'] ?? null;
        $token = $result['fields']['Token'] ?? '';

        if ($status === '0' && ctype_digit($token) && (int) $token > 0) {
            return GatewayStartResult::redirect(self::PAYMENT_URL.'?Token='.$token, $token, ['Status' => 0, 'OrderId' => $orderId]);
        }

        if ($status === null) {
            return GatewayStartResult::failed('پاسخ نامعتبر از وب‌سرویس بانک پارسیان', raw: ['fault' => $result['fault'], 'status' => $result['status']]);
        }

        return GatewayStartResult::failed(
            self::message($status, $result['fields']['Message'] ?? ''),
            retryable: $status === '-1',
            raw: ['Status' => $status, 'OrderId' => $orderId],
        );
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        // کلیدهای بازگشت در پیاده‌سازی‌ها با حروف مختلف دیده شدن (Token/token، status/Status)
        $callback = array_change_key_case(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $request->callback), CASE_LOWER);
        $token = $request->token ?? ($callback['token'] ?? null);
        $fail = fn (string $message, array $raw = [], bool $cancelled = false) => new GatewayVerifyResult(false, $token, message: $message, cancelledByUser: $cancelled, raw: $raw);
        $mismatch = 'اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد';

        if ($request->transactionId === null || ! filled($request->token)) {
            return $fail('شناسه‌ی تراکنش برای تایید پرداخت بانک پارسیان در دسترس نیست');
        }

        if (filled($callback['token'] ?? null) && ! hash_equals($request->token, $callback['token'])) {
            return $fail($mismatch);
        }
        if (filled($callback['orderid'] ?? null) && $callback['orderid'] !== (string) $request->transactionId) {
            return $fail($mismatch);
        }
        if (filled($callback['amount'] ?? null) && (int) preg_replace('/\D/', '', $callback['amount']) !== $request->amountRial) {
            return $fail($mismatch);
        }
        $terminal = (string) ($this->credentials['terminal_id'] ?? '');
        if ($terminal !== '' && filled($callback['terminalno'] ?? null) && $callback['terminalno'] !== $terminal) {
            return $fail($mismatch);
        }

        $status = $callback['status'] ?? '';
        if ($status === '') {
            return $fail('نتیجه‌ی پرداخت از بانک پارسیان دریافت نشد');
        }
        if ($status !== '0') {
            return $fail(self::message($status), ['status' => $status], cancelled: $status === '-138');
        }

        // قبل از تایید: این رسید (توکن) فقط مال همین تراکنش؛ callbackهای هم‌زمان همین تراکنش همدیگه رو می‌بینن
        if (! GatewayReceipt::claim($this->key(), $request->token, $request->transactionId)) {
            return $fail('این رسید پرداخت قبلاً برای تراکنش دیگری استفاده شده است', ['Token' => $request->token]);
        }

        $raw = array_filter(['Token' => $request->token, 'OrderId' => (string) $request->transactionId, 'HashCardNumber' => $callback['hashcardnumber'] ?? null], 'filled');

        $confirm = $this->call(self::CONFIRM_URL, self::CONFIRM_NS, 'ConfirmPayment', ['Token' => $request->token], tries: 3);
        if (! $confirm['answered']) {
            return $fail(self::UNANSWERED_MESSAGE, $raw + ['unanswered' => true]);
        }

        $confirmStatus = $confirm['fields']['Status'] ?? null;
        $rrn = $confirm['fields']['RRN'] ?? '';
        $raw['confirm'] = $confirmStatus;

        if ($confirmStatus === '0' && ctype_digit($rrn) && (int) $rrn > 0) {
            return new GatewayVerifyResult(true, $token, refId: $rrn, cardPan: filled($confirm['fields']['CardNumberMasked'] ?? null) ? $confirm['fields']['CardNumberMasked'] : null, raw: $raw + ['RRN' => $rrn]);
        }

        // پاسخ تایید قبلی گم شده بود و مشتری صفحه رو refresh کرد: RRN از بازگشت بانک
        if (in_array($confirmStatus, ['-1533', '2'], true) && ctype_digit($callback['rrn'] ?? '') && (int) $callback['rrn'] > 0) {
            return new GatewayVerifyResult(true, $token, refId: $callback['rrn'], raw: $raw + ['RRN' => $callback['rrn'], 'already_confirmed' => true]);
        }

        if ($confirmStatus === null) {
            return $fail('پاسخ نامعتبر از بانک پارسیان در تایید پرداخت', $raw + ['fault' => $confirm['fault']]);
        }

        return $fail(self::message($confirmStatus, $confirm['fields']['Message'] ?? ''), $raw);
    }

    /** برگشت کل مبلغ به کارت (پارسیان تراکنشِ تاییدشده رو هم برمی‌گردونه، تا مهلتی که بانک تعیین کرده). */
    public function reverseTransaction(PaymentTransaction $transaction): bool
    {
        $token = (string) $transaction->gateway_receipt;
        if ($transaction->driver !== $this->key() || ! ctype_digit($token)) {
            return false;
        }

        $result = $this->call(self::REVERSAL_URL, self::REVERSAL_NS, 'ReversalRequest', ['Token' => $token], tries: 3, soap12: true);
        $status = $result['answered'] ? ($result['fields']['Status'] ?? null) : null;

        if ($status === '-1549') {
            Log::warning('Parsian reversal window has passed', ['transaction_id' => $transaction->id, 'salon_id' => $transaction->salon_id]);
        } elseif ($result['answered'] && $status === null) {
            Log::error('Parsian reversal returned no result — check the ReversalService namespace', ['transaction_id' => $transaction->id, 'fault' => $result['fault'], 'status' => $result['status']]);
        }

        return in_array($status, ['0', '-1551'], true);
    }

    public static function message(string $status, string $fallback = ''): string
    {
        return match ($status) {
            '-138' => 'پرداخت توسط کاربر لغو شد',
            '-1' => 'خطای سرور بانک پارسیان',
            '-100' => 'پذیرنده (ترمینال پارسیان) غیرفعال است',
            '-101' => 'پذیرنده احراز هویت نشد — PIN یا IP سرور را بررسی کنید',
            '-103' => 'قابلیت خرید برای این پذیرنده فعال نیست',
            '-111' => 'مبلغ تراکنش بیش از حد مجاز پذیرنده است',
            '-112' => 'شماره‌ی سفارش تکراری است',
            '-126' => 'کد شناسایی پذیرنده (PIN) معتبر نیست',
            '-127' => 'آدرس بازگشت معتبر نیست',
            '-128' => 'IP سرور نزد پارسیان معتبر نیست',
            '-130' => 'زمان توکن پرداخت پارسیان منقضی شده است',
            '-131' => 'توکن پرداخت پارسیان نامعتبر است',
            '-132' => 'مبلغ تراکنش کمتر از حداقل مجاز است',
            '-1528' => 'اطلاعات پرداخت در بانک پارسیان یافت نشد',
            '-1530' => 'پذیرنده مجاز به تایید این تراکنش نیست',
            '-1533', '2' => 'این تراکنش قبلاً تایید شده است',
            '-1540' => 'تایید تراکنش در بانک پارسیان ناموفق بود',
            '-1549' => 'مهلت برگشت تراکنش تمام شده است',
            '-1551' => 'تراکنش قبلاً برگشت خورده است',
            '51' => 'موجودی کارت کافی نیست',
            '55' => 'رمز کارت نادرست است',
            '54', '33' => 'تاریخ انقضای کارت گذشته است',
            '56' => 'کارت نامعتبر است',
            '61' => 'مبلغ تراکنش بیش از حد مجاز کارت است',
            '75' => 'تعداد دفعات ورود رمز اشتباه بیش از حد مجاز است',
            default => $fallback !== '' ? $fallback : 'خطا در درگاه بانک پارسیان (کد: '.$status.')',
        };
    }
}
