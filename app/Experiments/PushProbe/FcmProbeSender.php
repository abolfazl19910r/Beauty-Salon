<?php

namespace App\Experiments\PushProbe;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * ارسال مستقیم به FCM HTTP v1 با حساب سرویس فایربیس، بدون پکیج اضافه (JWT با openssl امضا می‌شود).
 *
 * دو راه: مستقیم از سرور (oauth2.googleapis.com و fcm.googleapis.com) یا از Worker کلودفلر
 * (experiments/push-probe/fcm-proxy). در راه دوم فقط آدرس درخواست عوض می‌شود؛ aud داخل JWT همان آدرس واقعی
 * گوگل می‌ماند، وگرنه گوگل امضا را رد می‌کند.
 */
class FcmProbeSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** @var array<string, string> توکن دسترسی هر راه، فقط در همین اجرای دستور */
    private array $accessTokens = [];

    private ?array $credentials = null;

    public function projectId(): string
    {
        return (string) $this->credentials()['project_id'];
    }

    /** دریافت توکن دسترسی گوگل؛ در دستور check هم برای سنجش راه استفاده می‌شود. */
    public function fetchAccessToken(bool $viaProxy): ProbeResult
    {
        $started = hrtime(true);
        $credentials = $this->credentials();

        try {
            $response = Http::asForm()
                ->timeout((int) config('push_probe.timeout'))
                ->post($this->tokenUrl($viaProxy), [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->signedJwt($credentials),
                ]);
        } catch (ConnectionException $e) {
            return new ProbeResult(false, null, $this->elapsed($started), 'اتصال برقرار نشد: '.$e->getMessage());
        }

        $token = $response->json('access_token');
        if ($response->successful() && is_string($token) && $token !== '') {
            $this->accessTokens[$this->key($viaProxy)] = $token;

            return new ProbeResult(true, $response->status(), $this->elapsed($started), 'توکن دسترسی گرفته شد');
        }

        return new ProbeResult(false, $response->status(), $this->elapsed($started), 'توکن دسترسی رد شد: '.$this->shorten($response->body()));
    }

    public function send(string $deviceToken, ProbeMessage $message, bool $viaProxy): ProbeResult
    {
        if (! isset($this->accessTokens[$this->key($viaProxy)])) {
            $auth = $this->fetchAccessToken($viaProxy);
            if (! $auth->ok) {
                return $auth;
            }
        }

        $started = hrtime(true);
        $payload = ['message' => [
            'token' => $deviceToken,
            'notification' => ['title' => $message->title(), 'body' => $message->body()],
            'data' => $message->data(),
            'android' => [
                // HIGH = پیام فوری؛ همان چیزی که اعلان‌های نوبت در اپ واقعی لازم دارند
                'priority' => 'HIGH',
                'ttl' => (int) config('push_probe.fcm.ttl').'s',
                'notification' => [
                    'channel_id' => (string) config('push_probe.fcm.channel_id'),
                    'notification_priority' => 'PRIORITY_MAX',
                    'default_sound' => true,
                ],
            ],
        ]];

        try {
            $response = Http::withToken($this->accessTokens[$this->key($viaProxy)])
                ->timeout((int) config('push_probe.timeout'))
                ->post($this->sendUrl($viaProxy), $payload);
        } catch (ConnectionException $e) {
            return new ProbeResult(false, null, $this->elapsed($started), 'اتصال برقرار نشد: '.$e->getMessage());
        }

        if ($response->successful()) {
            return new ProbeResult(true, $response->status(), $this->elapsed($started), (string) $response->json('name'));
        }

        // خطای FCM: errorCode مثل UNREGISTERED (توکن کهنه) یا SENDER_ID_MISMATCH (پروژه‌ی فایربیس اشتباه)
        $errorCode = collect($response->json('error.details') ?? [])->pluck('errorCode')->filter()->first();
        $status = $response->json('error.status');

        return new ProbeResult(false, $response->status(), $this->elapsed($started), trim(implode(' ', array_filter([
            $errorCode, $status, $this->shorten((string) $response->json('error.message', $response->body())),
        ]))));
    }

    public function tokenUrl(bool $viaProxy): string
    {
        return $viaProxy ? $this->proxyBase().'/token' : (string) config('push_probe.fcm.oauth_url');
    }

    public function sendUrl(bool $viaProxy): string
    {
        $base = $viaProxy ? $this->proxyBase() : rtrim((string) config('push_probe.fcm.api_url'), '/');

        return "{$base}/v1/projects/{$this->projectId()}/messages:send";
    }

    public function proxyBase(): string
    {
        $proxy = (string) config('push_probe.fcm.proxy');
        if ($proxy === '') {
            throw new RuntimeException('PUSH_PROBE_FCM_PROXY در .env تنظیم نشده است (آدرس Worker به‌همراه بخش مخفی مسیر).');
        }

        return rtrim($proxy, '/');
    }

    /** @return array{client_email: string, private_key: string, project_id: string, token_uri?: string} */
    private function credentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = (string) config('push_probe.fcm.credentials');
        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException('فایل حساب سرویس فایربیس پیدا نشد؛ مسیر آن را در PUSH_PROBE_FCM_CREDENTIALS بگذارید.');
        }

        $json = json_decode((string) file_get_contents($path), true);
        foreach (['client_email', 'private_key', 'project_id'] as $key) {
            if (! is_array($json) || empty($json[$key])) {
                throw new RuntimeException("فایل حساب سرویس فایربیس کلید «{$key}» ندارد (فایل google-services.json اشتباهی است؟).");
            }
        }

        return $this->credentials = $json;
    }

    private function signedJwt(array $credentials): string
    {
        $now = time();
        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                // همیشه آدرس واقعی گوگل، حتی وقتی درخواست از Worker می‌رود
                'aud' => $credentials['token_uri'] ?? config('push_probe.fcm.oauth_url'),
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $key = openssl_pkey_get_private($credentials['private_key']);
        if ($key === false || ! openssl_sign(implode('.', $segments), $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('کلید خصوصی فایل حساب سرویس خوانده نشد.');
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function key(bool $viaProxy): string
    {
        return $viaProxy ? 'proxy' : 'direct';
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function shorten(string $text): string
    {
        return mb_strimwidth(preg_replace('/\s+/', ' ', $text), 0, 200, '…');
    }
}
