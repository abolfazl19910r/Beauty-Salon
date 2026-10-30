<?php

namespace App\Services\Payment\Zarinpal;

use App\Models\Salon;
use App\Models\User;
use App\Models\ZarinpalConnection;
use App\Payments\PaymentReturnUrl;
use App\Support\ZarinpalMerchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * ⭐ «اتصال حساب زرین‌پال» (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰) — OAuth 2.0 Authorization Code + PKCE طبق `/apiDocs/connect.html` و GraphQL
 * طبق `/apiDocs/query/terminal.html` و `bankaccount.html` (فایل مرجع Project: بخش ۴).
 *
 * ۱) authorizeUrl(): code_verifier تصادفی (۳۲ بایت base64url)، code_challenge = base64url(sha256)، state؛ هر سه در نشست.
 * ۲) complete(): state یک‌بارمصرف را با hash_equals می‌سنجد، code را با grant_type=authorization_code (form-urlencoded) به توکن
 *    تبدیل و رمزشده ذخیره می‌کند، بعد sync().
 * ۳) sync(): `Terminals`؛ درگاه ACTIVE با `domain` = دامنه‌ی سالن (PaymentReturnUrl::domain) ← `key` همان merchant_id ردیف زرین‌پال.
 *    درگاه هم‌دامنه‌ی غیرفعال یا نبودنش ← هیچ مرچنتی برداشته نمی‌شود؛ مالک از همان صفحه requestTerminal() می‌زند.
 * ۴) requestTerminal(): `TerminalAdd` با mcc_id پیکربندی، حساب بانکی انتخابی (`BankAccounts`)، دامنه‌ی سالن.
 * هشدار مستندات: فرایند فقط سمت سرور؛ توکن هرگز به مرورگر/اپ نمی‌رود. refresh_token در این روش مستند نیست ← بعد از انقضا اتصال دوباره.
 */
class ZarinpalConnectService
{
    public const SESSION_KEY = 'zarinpal_connect';

    public static function enabled(): bool
    {
        return filled(config('services.zarinpal.connect.client_id'));
    }

    public function redirectUri(): string
    {
        return (string) (config('services.zarinpal.connect.redirect_uri')
            ?: rtrim((string) config('app.url'), '/').route('admin.payment-gateways.zarinpal.callback', [], false));
    }

    public function authorizeUrl(Salon $salon): string
    {
        $verifier = self::base64Url(random_bytes(32));
        $state = Str::random(40);
        session([self::SESSION_KEY => ['verifier' => $verifier, 'state' => $state, 'salon_id' => $salon->id]]);

        return $this->oauthUrl('/authorizeInit').'?'.http_build_query([
            'scope' => (string) config('services.zarinpal.connect.scopes'),
            'response_type' => 'code',
            'client_id' => (string) config('services.zarinpal.connect.client_id'),
            'code_challenge' => self::base64Url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public function complete(Salon $salon, User $by, ?string $code, ?string $state, ?string $error = null): array
    {
        $pending = session()->pull(self::SESSION_KEY);

        if (! is_array($pending) || ! is_string($state) || ! hash_equals((string) ($pending['state'] ?? ''), $state)
            || (int) ($pending['salon_id'] ?? 0) !== (int) $salon->id) {
            return self::fail('درخواست اتصال نامعتبر یا منقضی است؛ دوباره «اتصال حساب زرین‌پال» را بزنید.');
        }
        if ($error || ! filled($code)) {
            return self::fail('اتصال در زرین‌پال تأیید نشد'.($error ? " ({$error})" : '').'.');
        }

        try {
            $response = Http::timeout(30)->acceptJson()->asForm()->post($this->oauthUrl('/token'), [
                'grant_type' => 'authorization_code',
                'client_id' => (string) config('services.zarinpal.connect.client_id'),
                'code' => $code,
                'code_verifier' => (string) $pending['verifier'],
                'redirect_uri' => $this->redirectUri(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Zarinpal connect: token request failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);

            return self::fail('ارتباط با زرین‌پال برقرار نشد؛ کمی بعد دوباره تلاش کنید.');
        }

        $token = (string) ($response->json('access_token') ?? '');
        if (! $response->successful() || $token === '') {
            Log::warning('Zarinpal connect: no access token', ['salon_id' => $salon->id, 'status' => $response->status(), 'errors' => $response->json('errors')]);

            return self::fail('زرین‌پال توکن دسترسی نداد؛ دوباره تلاش کنید.');
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 0);
        ZarinpalConnection::updateOrCreate(['salon_id' => $salon->id], [
            'connected_by' => $by->id,
            'access_token' => $token,
            'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
            'requested_terminal' => null,
        ]);

        return $this->sync($salon);
    }

    /**
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public function sync(Salon $salon): array
    {
        $connection = $salon->zarinpalConnection()->first();
        if (! $connection?->tokenUsable()) {
            return self::fail('اتصال حساب زرین‌پال منقضی شده است؛ دوباره «اتصال حساب زرین‌پال» را بزنید.');
        }

        $data = $this->graphql($connection, 'query { Terminals { id status domain key name } }');
        if ($data === null) {
            return self::fail('فهرست درگاه‌ها از زرین‌پال خوانده نشد؛ کمی بعد «به‌روزرسانی از زرین‌پال» را بزنید.');
        }

        $domain = PaymentReturnUrl::domain($salon);
        $terminals = collect((array) ($data['Terminals'] ?? []))->filter(fn ($t) => is_array($t))->values();
        $sameDomain = $terminals->filter(fn (array $t) => self::sameDomain((string) ($t['domain'] ?? ''), $domain));
        $active = $sameDomain->first(fn (array $t) => strtoupper((string) ($t['status'] ?? '')) === 'ACTIVE' && ZarinpalMerchant::validOrNull((string) ($t['key'] ?? '')));

        $connection->update([
            // بی مرچنت: فقط برای نمایش در پنل
            'terminals' => $terminals->map(fn (array $t) => ['id' => (string) ($t['id'] ?? ''), 'status' => (string) ($t['status'] ?? ''), 'domain' => (string) ($t['domain'] ?? ''), 'name' => (string) ($t['name'] ?? '')])->all(),
            'terminal_id' => $active ? (string) $active['id'] : null,
            'synced_at' => now(),
        ]);

        if (! $active) {
            return [
                'ok' => true,
                'level' => 'warning',
                'message' => $sameDomain->isNotEmpty()
                    ? "حساب زرین‌پال وصل شد؛ درگاه دامنه‌ی {$domain} هنوز فعال نیست (وضعیت: ".$sameDomain->pluck('status')->implode('، ').').'
                    : "حساب زرین‌پال وصل شد ولی درگاهی با دامنه‌ی {$domain} ندارد؛ از همین صفحه درخواست درگاه بدهید.",
            ];
        }

        $this->applyMerchant($salon, (string) ZarinpalMerchant::validOrNull((string) $active['key']));

        return ['ok' => true, 'level' => 'success', 'message' => "حساب زرین‌پال وصل شد و کد پذیرنده‌ی درگاه {$domain} خودکار ثبت شد."];
    }

    /** @return list<array{id: string, iban: string, status: string, holder_name: string, bank: string}> */
    public function bankAccounts(Salon $salon): array
    {
        $connection = $salon->zarinpalConnection()->first();
        if (! $connection?->tokenUsable()) {
            return [];
        }

        $data = $this->graphql($connection, 'query getBankAccounts { resource: BankAccounts { id iban status is_legal holder_name type issuing_bank { name slug } } }');

        return collect((array) ($data['resource'] ?? []))->filter(fn ($a) => is_array($a))->map(fn (array $a) => [
            'id' => (string) ($a['id'] ?? ''),
            'iban' => (string) ($a['iban'] ?? ''),
            'status' => (string) ($a['status'] ?? ''),
            'holder_name' => (string) ($a['holder_name'] ?? ''),
            'bank' => (string) ($a['issuing_bank']['name'] ?? ''),
        ])->values()->all();
    }

    /**
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public function requestTerminal(Salon $salon, string $bankAccountId, string $supportPhone, string $name): array
    {
        $connection = $salon->zarinpalConnection()->first();
        if (! $connection?->tokenUsable()) {
            return self::fail('اتصال حساب زرین‌پال منقضی شده است؛ دوباره وصل شوید.');
        }
        $mcc = (string) config('services.zarinpal.connect.mcc_id');
        if ($mcc === '') {
            return self::fail('دسته‌بندی کسب‌وکار (mcc_id) زرین‌پال تنظیم نشده است؛ با پشتیبانی پلتفرم تماس بگیرید.');
        }

        $domain = PaymentReturnUrl::domain($salon);
        $data = $this->graphql($connection,
            'mutation TerminalAdd($mcc_id: ID!, $domain: String!, $support_phone: CellNumber!, $name: String!, $bank_account_id: ID!) '
            .'{ TerminalAdd(mcc_id: $mcc_id, domain: $domain, support_phone: $support_phone, name: $name, bank_account_id: $bank_account_id) '
            .'{ id mcc_id preferred_bank_account_id domain support_phone key name status logo created_at updated_at deleted_at } }',
            ['mcc_id' => $mcc, 'bank_account_id' => $bankAccountId, 'domain' => $domain, 'support_phone' => $supportPhone, 'name' => $name],
        );
        $terminal = $data['TerminalAdd'] ?? null;
        if (! is_array($terminal)) {
            return self::fail('درخواست درگاه در زرین‌پال ثبت نشد؛ اطلاعات را بررسی و دوباره تلاش کنید.');
        }

        $connection->update(['requested_terminal' => [
            'id' => (string) ($terminal['id'] ?? ''), 'status' => (string) ($terminal['status'] ?? ''),
            'domain' => (string) ($terminal['domain'] ?? $domain), 'name' => (string) ($terminal['name'] ?? $name), 'at' => now()->toIso8601String(),
        ]]);

        if (strtoupper((string) ($terminal['status'] ?? '')) === 'ACTIVE' && ($key = ZarinpalMerchant::validOrNull((string) ($terminal['key'] ?? '')))) {
            $this->applyMerchant($salon, (string) $key);
            $connection->update(['terminal_id' => (string) $terminal['id']]);

            return ['ok' => true, 'level' => 'success', 'message' => 'درگاه زرین‌پال ساخته و فعال شد؛ کد پذیرنده خودکار ثبت شد.'];
        }

        return ['ok' => true, 'level' => 'success', 'message' => "درخواست درگاه برای {$domain} در زرین‌پال ثبت شد و در انتظار تأیید زرین‌پال است؛ پس از تأیید «به‌روزرسانی از زرین‌پال» را بزنید."];
    }

    public function disconnect(Salon $salon): void
    {
        $salon->zarinpalConnection()->delete();
    }

    /** ردیف زرین‌پال سالن: مرچنت تازه و فعال (همان رفتار ورود دستی کد پذیرنده). */
    private function applyMerchant(Salon $salon, string $merchantId): void
    {
        DB::transaction(function () use ($salon, $merchantId) {
            $row = $salon->paymentGateways()->where('driver', 'zarinpal')->lockForUpdate()->first();
            if ($row) {
                $row->update(['credentials' => ['merchant_id' => $merchantId] + (array) $row->credentials, 'is_active' => true]);

                return;
            }
            $salon->paymentGateways()->create([
                'driver' => 'zarinpal', 'credentials' => ['merchant_id' => $merchantId], 'is_active' => true,
                'priority' => ((int) $salon->paymentGateways()->max('priority')) + 1,
            ]);
        });
    }

    /** @return array<string, mixed>|null */
    private function graphql(ZarinpalConnection $connection, string $query, array $variables = []): ?array
    {
        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->withToken((string) $connection->access_token)
                ->post((string) config('services.zarinpal.connect.graphql_url'), ['query' => $query, 'variables' => $variables ?: null]);
        } catch (Throwable $e) {
            Log::warning('Zarinpal connect: GraphQL request failed', ['salon_id' => $connection->salon_id, 'error' => $e->getMessage()]);

            return null;
        }

        $data = $response->json('data');
        if (! $response->successful() || ! is_array($data) || ! empty($response->json('errors'))) {
            Log::warning('Zarinpal connect: GraphQL error', ['salon_id' => $connection->salon_id, 'status' => $response->status(), 'errors' => $response->json('errors')]);

            return null;
        }

        return $data;
    }

    private function oauthUrl(string $path): string
    {
        return rtrim((string) config('services.zarinpal.connect.oauth_url'), '/').$path;
    }

    /** `https://www.Rasta.arisheh.ir/` و `rasta.arisheh.ir` یکی‌اند. */
    public static function sameDomain(string $registered, string $expected): bool
    {
        $host = parse_url(str_contains($registered, '://') ? $registered : 'http://'.$registered, PHP_URL_HOST) ?: $registered;
        $host = preg_replace('/^www\./', '', mb_strtolower(rtrim(trim((string) $host), '.')));

        return $host !== '' && $host === mb_strtolower($expected);
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** @return array{ok: false, level: 'error', message: string} */
    private static function fail(string $message): array
    {
        return ['ok' => false, 'level' => 'error', 'message' => $message];
    }
}
