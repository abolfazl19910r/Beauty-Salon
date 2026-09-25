<?php

namespace App\Payments\Drivers;

use App\Models\SalonPaymentGateway;
use App\Payments\Contracts\PayoutDriver;
use App\Payments\PayoutRequest;
use App\Payments\PayoutResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⭐ تسویه‌ی وندار (مرحله‌ی ۳ چند درگاه، ۲۰۲۶-۰۹-۲۶) — واریز برداشت متخصص از کیف پول وندارِ خود سالن به شبای او.
 *
 * منبع: مستند رسمی وندار (vandarpay/docs: settlement و auth) و فهرست endpointهای docs.vandar.io که همین مسیرها رو
 * هنوز دارن:
 * - POST https://api.vandar.io/v3/business/{business}/settlement/store، هدر Authorization: Bearer {access_token}،
 *   بدنه {amount, iban, track_id, description} → {status: 1, data: {settlement: [{id, transaction_id, status, …}]}}.
 * - ✅ واحد مبلغ **تومان** (حداقل ۵٬۰۰۰): متن مستند «مبلغ تراکنش به تومان» و نمونه‌ی رسمی‌اش همین رو نشون می‌ده —
 *   درخواست amount: 5000 → پاسخ amount: 50000 (ریال) و amount_toman: 5000. حتی اگر روزی ریال بشه، فرستادن تومان
 *   فقط ۱/۱۰ واریز می‌کنه (باقی در کیف پول می‌مونه) — هرگز ۱۰ برابر.
 * - track_id «به ازای هر درخواست تسویه یکتا»: mahru-wd-{شناسه‌ی برداشت} — دوباره فرستادن یک برداشت، تسویه‌ی دوم
 *   نمی‌سازه.
 * - توکن: access_token پنج روزه (expires_in: 432000)؛ POST https://api.vandar.io/v3/refreshtoken {refreshtoken} →
 *   access_token و refresh_token **جدید** — هر دو دوباره ذخیره می‌شن (refresh_token قبلی دیگه معتبر نیست). تمدید
 *   روزانه با payouts:refresh-vandar-tokens، و روی ۴۰۱ همین‌جا (با قفل، تا تمدید هم‌زمان یک توکن رو دو بار مصرف نکنه).
 *
 * ⚠️ نتیجه‌ی نامعلوم (قطع اتصال یا ۵xx بعد از فرستادن): ممکنه وندار ثبت کرده باشه. نه «ناموفق» (برگشت به کیف پول
 * متخصص = احتمال پرداخت دوباره) و نه تکرار کورکورانه؛ unknown=true → برداشت در processing می‌مونه تا مدیر در
 * داشبورد وندار ببینه و دستی تأیید یا رد کنه.
 *
 * credentials: payout_business (نام انگلیسی کسب‌وکار در وندار)، payout_access_token، payout_refresh_token.
 */
class VandarPayoutDriver implements PayoutDriver
{
    public const API = 'https://api.vandar.io';

    public const MIN_TOMAN = 5000;

    public function __construct(
        private array $credentials,
        private readonly ?SalonPaymentGateway $gateway = null,
    ) {}

    public function key(): string
    {
        return 'vandar';
    }

    public static function isConfigured(array $credentials): bool
    {
        return filled($credentials['payout_business'] ?? null)
            && filled($credentials['payout_access_token'] ?? null)
            && filled($credentials['payout_refresh_token'] ?? null);
    }

    public static function trackId(string $reference): string
    {
        return 'mahru-wd-'.$reference;
    }

    public function payout(PayoutRequest $request): PayoutResult
    {
        $toman = intdiv($request->amountRial, 10);
        if ($toman < self::MIN_TOMAN) {
            return new PayoutResult(false, message: 'حداقل مبلغ تسویه‌ی وندار ۵٬۰۰۰ تومان است');
        }

        $body = [
            'amount' => $toman,
            'iban' => $request->iban,
            'track_id' => self::trackId($request->reference),
            'description' => mb_substr($request->description, 0, 250),
        ];

        $response = $this->store($body);
        if ($response?->status() === 401 && $this->refreshTokens()) {
            $response = $this->store($body); // ۴۰۱ یعنی قبل از پردازش رد شد — تکرارش امنه
        }

        if ($response === null || $response->serverError()) {
            Log::error('VandarPayoutDriver: نتیجه‌ی تسویه نامعلوم — بررسی دستی در داشبورد وندار', [
                'reference' => $request->reference, 'track_id' => $body['track_id'], 'status' => $response?->status(),
            ]);

            return new PayoutResult(
                false,
                message: 'پاسخ وندار دریافت نشد؛ ممکن است تسویه ثبت شده باشد. شناسه‌ی پیگیری '.$body['track_id'].' را در داشبورد وندار بررسی کنید.',
                raw: ['track_id' => $body['track_id'], 'status' => $response?->status()],
                unknown: true,
            );
        }

        $json = (array) $response->json();
        $settlement = (array) ($json['data']['settlement'][0] ?? []);

        if ($response->successful() && (int) ($json['status'] ?? 0) === 1 && filled($settlement['id'] ?? null)) {
            return new PayoutResult(
                true,
                referenceCode: (string) ($settlement['transaction_id'] ?? $settlement['id']),
                payoutId: (string) $settlement['id'],
                raw: ['track_id' => $body['track_id']] + $settlement,
            );
        }

        Log::error('VandarPayoutDriver: درخواست تسویه ناموفق', ['reference' => $request->reference, 'status' => $response->status(), 'response' => $json]);

        return new PayoutResult(false, message: self::errorMessage($response, $json), raw: $json + ['track_id' => $body['track_id']]);
    }

    private function store(array $body): ?Response
    {
        try {
            return Http::timeout(30)->acceptJson()->asJson()
                ->withToken((string) ($this->credentials['payout_access_token'] ?? ''))
                ->post(self::API.'/v3/business/'.rawurlencode((string) ($this->credentials['payout_business'] ?? '')).'/settlement/store', $body);
        } catch (Throwable $e) {
            Log::warning('VandarPayoutDriver: خطای اتصال', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * تمدید توکن‌ها و ذخیره‌ی هر دو روی ردیف درگاه. زیر قفل: اگر در همین فاصله کس دیگری (کار زمان‌بندی‌شده یا یک
     * تسویه‌ی هم‌زمان) تمدید کرده باشه، همون توکن تازه استفاده می‌شه و refresh_token مصرف‌شده دوباره فرستاده نمی‌شه.
     */
    public function refreshTokens(): bool
    {
        $usedAccessToken = (string) ($this->credentials['payout_access_token'] ?? '');
        $lock = Cache::lock('vandar-payout-token:'.($this->gateway?->id ?? md5((string) ($this->credentials['payout_refresh_token'] ?? ''))), 30);

        try {
            return (bool) $lock->block(15, function () use ($usedAccessToken) {
                if ($this->gateway) {
                    $stored = (array) $this->gateway->fresh()?->credentials;
                    if (filled($stored['payout_access_token'] ?? null) && $stored['payout_access_token'] !== $usedAccessToken) {
                        $this->credentials = $stored + $this->credentials;

                        return true;
                    }
                }

                try {
                    $response = Http::timeout(30)->acceptJson()->asJson()
                        ->post(self::API.'/v3/refreshtoken', ['refreshtoken' => (string) ($this->credentials['payout_refresh_token'] ?? '')]);
                } catch (Throwable $e) {
                    Log::warning('VandarPayoutDriver: تمدید توکن — خطای اتصال', ['gateway_id' => $this->gateway?->id, 'error' => $e->getMessage()]);

                    return false;
                }

                $json = (array) $response->json();
                if (! $response->successful() || ! filled($json['access_token'] ?? null) || ! filled($json['refresh_token'] ?? null)) {
                    Log::error('VandarPayoutDriver: تمدید توکن ناموفق — توکن‌های تازه را از داشبورد وندار در «درگاه‌های پرداخت» وارد کنید', [
                        'gateway_id' => $this->gateway?->id, 'status' => $response->status(),
                    ]);

                    return false;
                }

                $this->credentials['payout_access_token'] = (string) $json['access_token'];
                $this->credentials['payout_refresh_token'] = (string) $json['refresh_token'];

                if ($this->gateway) {
                    $current = (array) $this->gateway->fresh()?->credentials;
                    $this->gateway->forceFill(['credentials' => array_merge($current, [
                        'payout_access_token' => $this->credentials['payout_access_token'],
                        'payout_refresh_token' => $this->credentials['payout_refresh_token'],
                    ])])->save();
                }

                return true;
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            return false;
        }
    }

    private static function errorMessage(Response $response, array $json): string
    {
        $detail = $json['error'] ?? $json['message'] ?? null;
        if (! $detail && ! empty($json['errors'])) {
            $detail = collect((array) $json['errors'])->flatten()->filter()->implode(' ');
        }

        return match (true) {
            $response->status() === 401 => 'توکن وندار نامعتبر است و تمدید هم نشد — توکن‌های تازه را در «درگاه‌های پرداخت» وارد کنید',
            $response->status() === 403 => 'این توکن وندار اجازه‌ی تسویه ندارد',
            default => 'درخواست تسویه توسط وندار پذیرفته نشد'.($detail ? ': '.$detail : ' (کد '.$response->status().')'),
        };
    }
}
