<?php

namespace App\Payments\Drivers;

use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\GatewayReceipt;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ⭐ درگاه مستقیم بانک سامان — پرداخت الکترونیک سامان (سپ)، مرحله‌ی ۲ چند درگاه.
 *
 * طبق «راهنمای استفاده از درگاه پرداخت اینترنتی» رسمی سپ (نگارش ۳٫۲، تیر ۱۴۰۲؛ endpointها و فیلدها در
 * نگارش‌های ۳٫۳ و ۳٫۶ همین‌ها هستن):
 * - توکن: POST https://sep.shaparak.ir/OnlinePG/OnlinePG با JSON
 *   {action: "token", TerminalId, Amount (ریال، عدد صحیح), ResNum (شماره‌ی خرید یکتای ما), RedirectUrl, CellNumber}
 *   → {status: 1, token} یا {status: -1, errorCode, errorDesc}. ⚠️ IP سرور باید نزد سپ ثبت شده باشه (کد ۸).
 * - انتقال: فرم POST خودکار با فیلد Token به همون آدرس (مستند: ورود باید از سایت پذیرنده باشه تا مرورگر
 *   Referer بفرسته — صفحه‌ی payments.gateway-redirect همین کار رو می‌کنه).
 * - بازگشت: POST به RedirectUrl با State / Status / RefNum (رسید دیجیتالی) / ResNum / TerminalId / MID /
 *   TraceNo / Rrn / Amount / SecurePan / Token. Status = 2 (State = OK) یعنی موفق.
 * - تایید: POST https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction
 *   {RefNum, TerminalNumber} ظرف ۳۰ دقیقه (وگرنه خود سپ برگشت می‌زنه) → {TransactionDetail: {RRN, RefNum,
 *   MaskedPan, TerminalNumber, OrginalAmount, AffectiveAmount, StraceNo, …}, ResultCode, Success}.
 *   ResultCode: ۰ موفق، ۲ درخواست تکراری (همون رسید قبلاً تایید شده)، منفی = خطا.
 * - برگشت (Reverse): همون بدنه به …/ReverseTransaction، حداکثر ۵۰ دقیقه بعد از تراکنش تاییدشده.
 *
 * ⚠️ مصرف دوباره‌ی رسید: سپ هر RefNum رو هر چند بار که بخوایم دوباره تایید می‌کنه و مستند صریحاً جلوگیری
 * از مصرف دوباره رو به عهده‌ی پذیرنده گذاشته. پاسخ verify هم ResNum رو برنمی‌گردونه، پس رسید با
 * App\Payments\GatewayReceipt (index یکتا) به همین تراکنش قفل می‌شه، قبل از هر verify.
 * ⚠️ مبلغ ناهمخوان: مستند می‌گه کل مبلغ باید به مشتری برگرده → Reverse و رد پرداخت.
 * ⚠️ پاسخ verify نرسید: اگه سپ در واقع تایید کرده باشه، دیگه خودش برگشت نمی‌زنه و پول مشتری بدون خدمت می‌مونه؛
 * این حالت با unanswered=true در raw علامت می‌خوره تا payments:reconcile بعد از بسته شدن مهلت ۳۰ دقیقه‌ای
 * verify (و قبل از مهلت ۵۰ دقیقه‌ای Reverse) کل مبلغ رو برگردونه.
 * credentials: ['terminal_id' => '...'] — سپ برای توکن/تایید رمز نمی‌خواد؛ امنیت با IP ثبت‌شده است.
 */
class SamanDriver implements PaymentGatewayDriver
{
    public const PAYMENT_URL = 'https://sep.shaparak.ir/OnlinePG/OnlinePG';

    public const VERIFY_URL = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction';

    public const REVERSE_URL = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction';

    /** کد عددی Status در بازگشت از سپ ← نام State (جدول وضعیت تراکنش مستند). */
    private const STATES = [
        'canceledbyuser' => 1,
        'ok' => 2,
        'failed' => 3,
        'sessionisnull' => 4,
        'invalidparameters' => 5,
        'merchantipaddressisinvalid' => 8,
        'tokennotfound' => 10,
        'tokenrequired' => 11,
        'terminalnotfound' => 12,
        'multisettlepolicyerrors' => 21,
    ];

    public function __construct(private readonly array $credentials) {}

    public function key(): string
    {
        return 'saman';
    }

    private function terminalId(): string
    {
        return (string) ($this->credentials['terminal_id'] ?? '');
    }

    private function http()
    {
        return Http::timeout(30)->acceptJson()->asJson();
    }

    /** موبایل ایرانی به شکل 09xxxxxxxxx؛ هر چیز دیگه فرستاده نمی‌شه (پارامتر نامعتبر = خطای ۵ و شکست پرداخت). */
    private static function cellNumber(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr((string) $mobile, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));

        return preg_match('/^(?:0098|98|0)?(9\d{9})$/', (string) $digits, $m) ? '0'.$m[1] : null;
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        $resNum = (string) ($request->transactionId ?? (int) (microtime(true) * 1000));

        try {
            $response = $this->http()->post(self::PAYMENT_URL, array_filter([
                'action' => 'token',
                'TerminalId' => $this->terminalId(),
                'Amount' => $request->amountRial,
                'ResNum' => $resNum,
                'RedirectUrl' => $request->callbackUrl,
                'CellNumber' => self::cellNumber($request->mobile),
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable) {
            return GatewayStartResult::failed('خطا در اتصال به درگاه بانک سامان. لطفاً دوباره تلاش کنید.', retryable: true);
        }

        $body = (array) $response->json();
        $token = (string) ($body['token'] ?? '');

        if ($response->successful() && (int) ($body['status'] ?? 0) === 1 && $token !== '') {
            return GatewayStartResult::postForm(self::PAYMENT_URL, $token, ['Token' => $token], ['status' => 1, 'ResNum' => $resNum]);
        }

        $code = (int) ($body['errorCode'] ?? 0);

        return GatewayStartResult::failed(
            $code ? self::stateMessage($code) : 'خطا در دریافت توکن از درگاه بانک سامان (HTTP '.$response->status().')',
            retryable: $response->serverError(),
            raw: $body ?: ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)],
        );
    }

    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
    {
        $callback = $request->callback;
        $token = $request->token ?? (isset($callback['Token']) ? (string) $callback['Token'] : null);
        $fail = fn (string $message, array $raw = [], bool $cancelled = false) => new GatewayVerifyResult(false, $token, message: $message, cancelledByUser: $cancelled, raw: $raw);

        if ($request->transactionId === null) {
            return $fail('شناسه‌ی تراکنش برای تایید پرداخت بانک سامان در دسترس نیست');
        }

        // بازگشت باید مال همین تراکنش و همین ترمینال باشه (نه پارامترهای یک پرداخت دیگه)
        if (isset($callback['Token'], $request->token) && ! hash_equals($request->token, (string) $callback['Token'])) {
            return $fail('اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد');
        }
        if (isset($callback['ResNum']) && (string) $callback['ResNum'] !== (string) $request->transactionId) {
            return $fail('اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد');
        }
        foreach (['TerminalId', 'MID'] as $field) {
            if (isset($callback[$field]) && (string) $callback[$field] !== '' && (string) $callback[$field] !== $this->terminalId()) {
                return $fail('اطلاعات بازگشتی بانک با این تراکنش همخوانی ندارد');
            }
        }

        $status = self::callbackStatus($callback);
        if ($status !== 2) {
            return $fail($status === null ? 'نتیجه‌ی پرداخت از بانک سامان دریافت نشد' : self::stateMessage($status), ['Status' => $status], cancelled: $status === 1);
        }

        $refNum = trim((string) ($callback['RefNum'] ?? ''));
        if ($refNum === '') {
            return $fail('رسید دیجیتالی پرداخت از بانک سامان دریافت نشد');
        }

        // ⚠️ قبل از verify: هر رسید فقط یک بار و فقط برای یک تراکنش
        if (! GatewayReceipt::claim($this->key(), $refNum, $request->transactionId)) {
            return $fail('این رسید پرداخت قبلاً برای تراکنش دیگری استفاده شده است', ['RefNum' => $refNum]);
        }

        $payload = ['RefNum' => $refNum, 'TerminalNumber' => (int) $this->terminalId()];

        try {
            // مستند: اگه پاسخ verify نرسید (نه اینکه خطا برگرده) باید دوباره تلاش کرد
            $response = $this->http()
                ->retry(3, 500, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->post(self::VERIFY_URL, $payload);
        } catch (Throwable) {
            return $fail('پاسخ تایید از بانک سامان دریافت نشد. اگر مبلغی کسر شده باشد، حداکثر ظرف ۷۲ ساعت به حساب شما برمی‌گردد.', ['RefNum' => $refNum, 'unanswered' => true]);
        }

        $body = self::normalize((array) $response->json());
        $code = isset($body['ResultCode']) ? (int) $body['ResultCode'] : null;

        if (! $response->successful() || ($body['Success'] ?? false) !== true || ! in_array($code, [0, 2], true)) {
            return $fail(self::resultMessage($code), $body + ['RefNum' => $refNum]);
        }

        $detail = self::normalize((array) ($body['TransactionDetail'] ?? []));

        if ((isset($detail['RefNum']) && (string) $detail['RefNum'] !== $refNum)
            || (isset($detail['TerminalNumber']) && (string) $detail['TerminalNumber'] !== $this->terminalId())) {
            return $fail('پاسخ تایید بانک با این تراکنش همخوانی ندارد', $body);
        }

        if (! isset($detail['OrginalAmount']) || (int) $detail['OrginalAmount'] !== $request->amountRial) {
            $reversed = $this->reverse($refNum);

            return $fail(
                'مبلغ پرداخت‌شده با مبلغ تراکنش همخوانی ندارد'.($reversed ? '؛ کل مبلغ به حساب شما برگشت داده شد' : ''),
                $body + ['reversed' => $reversed],
            );
        }

        return new GatewayVerifyResult(
            true,
            $token,
            refId: (string) ($detail['RRN'] ?? $callback['Rrn'] ?? $callback['RRN'] ?? $detail['StraceNo'] ?? $refNum),
            cardPan: $detail['MaskedPan'] ?? ($callback['SecurePan'] ?? null),
            raw: $body + ['RefNum' => $refNum, 'TraceNo' => $callback['TraceNo'] ?? ($detail['StraceNo'] ?? null)],
        );
    }

    /**
     * برگشت کل مبلغ یک تراکنش تاییدشده به کارت مشتری (حداکثر ۵۰ دقیقه بعد از تراکنش). ResultCode ۲ یعنی
     * قبلاً برگشت خورده.
     */
    public function reverse(string $refNum): bool
    {
        try {
            $response = $this->http()->post(self::REVERSE_URL, ['RefNum' => $refNum, 'TerminalNumber' => (int) $this->terminalId()]);
        } catch (Throwable) {
            return false;
        }

        $body = self::normalize((array) $response->json());

        return $response->successful() && ($body['Success'] ?? false) === true && in_array((int) ($body['ResultCode'] ?? -1), [0, 2], true);
    }

    /** Status عددی بازگشت؛ اگه نبود، از State متنی. null = بانک نتیجه‌ای نفرستاده. */
    private static function callbackStatus(array $callback): ?int
    {
        if (isset($callback['Status']) && is_numeric($callback['Status'])) {
            return (int) $callback['Status'];
        }

        $state = strtolower(trim((string) ($callback['State'] ?? '')));

        return $state === '' ? null : (self::STATES[$state] ?? 3);
    }

    /** نمونه‌ی پاسخ مستند کلید « TransactionDetail» رو با فاصله‌ی اضافه نوشته — کلیدها trim می‌شن. */
    private static function normalize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[is_string($key) ? trim($key) : $key] = $value;
        }

        return $out;
    }

    public static function stateMessage(int $code): string
    {
        return match ($code) {
            1 => 'پرداخت توسط کاربر لغو شد',
            2 => 'پرداخت با موفقیت انجام شد',
            3 => 'پرداخت انجام نشد',
            4 => 'زمان پرداخت در درگاه بانک سامان به پایان رسید',
            5 => 'پارامترهای ارسالی به درگاه بانک سامان نامعتبر است',
            8 => 'IP سرور نزد پرداخت الکترونیک سامان ثبت نشده است',
            10 => 'توکن پرداخت بانک سامان یافت نشد یا منقضی شده است',
            11 => 'این ترمینال سامان فقط پرداخت توکنی می‌پذیرد',
            12 => 'شماره ترمینال بانک سامان یافت نشد',
            21 => 'محدودیت‌های تسهیم (چند حسابی) ترمینال سامان رعایت نشده است',
            default => 'خطا در درگاه بانک سامان (کد: '.$code.')',
        };
    }

    public static function resultMessage(?int $code): string
    {
        return match ($code) {
            -2 => 'تراکنش در بانک سامان یافت نشد',
            -6 => 'بیش از نیم ساعت از تراکنش گذشته و بانک آن را برگشت زده است',
            -104 => 'ترمینال بانک سامان غیرفعال است',
            -105 => 'ترمینال بانک سامان در سیستم موجود نیست',
            -106 => 'IP سرور برای تایید تراکنش نزد پرداخت الکترونیک سامان مجاز نیست',
            null => 'پاسخ نامعتبر از بانک سامان در تایید پرداخت',
            default => 'تایید پرداخت در بانک سامان ناموفق بود (کد: '.$code.')',
        };
    }
}
