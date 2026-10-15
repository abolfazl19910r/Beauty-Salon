<?php

namespace App\Services\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * FCM HTTP v1 با حساب سرویس فایربیس، بدون پکیج (JWT با openssl؛ همان روش کیت بسته‌ی ۰).
 *
 * درس‌های بسته‌ی ۰: توکن دسترسی گوگل یک ساعت عمر دارد — با زمان انقضا در cache نگه داشته و ۵ دقیقه پیش از آن تازه می‌شود؛
 * 401 یک بار با توکن تازه تکرار می‌شود (پچ 0025 کیت). شکست DNS/اتصال به گوگل «outage» است، نه گم شدن پیام: job با
 * فاصله دوباره امتحان می‌کند و پیام فوری/عادی همان لحظه پیامک می‌شود.
 */
class FcmClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const REFRESH_MARGIN = 300;

    private const CACHE_KEY = 'push:fcm:access_token';

    private ?array $credentials = null;

    public function configured(): bool
    {
        return (bool) config('push.fcm.credentials');
    }

    /**
     * @param  array<string, string>  $data  همه‌ی مقدارها رشته (قاعده‌ی FCM)
     */
    public function send(string $deviceToken, string $title, string $body, array $data, PushUrgency $urgency): PushResult
    {
        try {
            $token = $this->accessToken();
        } catch (ConnectionException $e) {
            return PushResult::outage('oauth: '.$this->shorten($e->getMessage()));
        } catch (FcmAuthException $e) {
            // پاسخ گوگل آمد ولی رد شد (کلید حذف‌شده، ساعت سرور غلط): پیام به این دستگاه نمی‌رسد تا درست شود — مثل قطعی
            return PushResult::outage('oauth: '.$e->getMessage());
        }

        $result = $this->post($token, $deviceToken, $title, $body, $data, $urgency);

        if ($result->status === 401) {
            Cache::forget(self::CACHE_KEY);
            try {
                $result = $this->post($this->accessToken(), $deviceToken, $title, $body, $data, $urgency);
            } catch (ConnectionException|FcmAuthException $e) {
                return PushResult::outage('oauth: '.$this->shorten($e->getMessage()));
            }
        }

        return $result;
    }

    /** برای دستور push:test و صفحه‌ی ابرمدیر: آیا توکن دسترسی گرفته می‌شود؟ */
    public function check(): PushResult
    {
        try {
            Cache::forget(self::CACHE_KEY);
            $this->accessToken();

            return PushResult::accepted();
        } catch (ConnectionException $e) {
            return PushResult::outage($this->shorten($e->getMessage()));
        } catch (FcmAuthException|RuntimeException $e) {
            return new PushResult(false, null, $e->getMessage());
        }
    }

    public function projectId(): string
    {
        return (string) $this->credentials()['project_id'];
    }

    private function post(string $accessToken, string $deviceToken, string $title, string $body, array $data, PushUrgency $urgency): PushResult
    {
        $android = [
            'priority' => 'HIGH',
            'ttl' => $urgency->ttlSeconds().'s',
        ];

        $message = ['token' => $deviceToken, 'android' => $android];

        // کلید «body» برای expo-notifications یک JSON است: همان content.data اعلان در اپ (لمس اعلان ← صفحه‌ی نوبت) و dataString در task
        $data['body'] = (string) json_encode($data, JSON_UNESCAPED_UNICODE);

        if (config('push.fcm.style') === 'notification') {
            $message['notification'] = ['title' => $title, 'body' => $body];
            $message['android']['notification'] = [
                'channel_id' => $urgency->androidChannel(),
                'notification_priority' => $urgency === PushUrgency::Info ? 'PRIORITY_DEFAULT' : 'PRIORITY_MAX',
                'default_sound' => true,
            ];
            $message['data'] = $data;
        } else {
            // data-only با اولویت بالا: expo-notifications در پس‌زمینه از title/message/channelId خودش اعلان می‌سازد (بومی، حتی اگر
            // JS اجرا نشود) و task پس‌زمینه‌ی اپ فقط رسید می‌فرستد؛ اپ باز: handler اپ همان را نشان می‌دهد و رسید می‌فرستد.
            $message['data'] = $data + ['title' => $title, 'message' => $body, 'channelId' => $urgency->androidChannel()];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout((int) config('push.fcm.timeout'))
                ->post($this->sendUrl(), ['message' => $message]);
        } catch (ConnectionException $e) {
            return PushResult::outage('send: '.$this->shorten($e->getMessage()));
        }

        if ($response->successful()) {
            return PushResult::accepted($response->status());
        }

        $errorCode = collect($response->json('error.details') ?? [])->pluck('errorCode')->filter()->first();
        $status = $response->status();
        $error = trim(implode(' ', array_filter([$errorCode, $response->json('error.status'), $this->shorten((string) $response->json('error.message', $response->body()))])));

        // UNREGISTERED (404) = اپ حذف/داده پاک شد؛ INVALID_ARGUMENT روی توکن = توکن خراب؛ SENDER_ID_MISMATCH = توکن پروژه‌ی دیگر
        $invalid = $status === 404 || in_array($errorCode, ['UNREGISTERED', 'SENDER_ID_MISMATCH'], true)
            || ($status === 400 && $errorCode === 'INVALID_ARGUMENT' && str_contains(strtolower($error), 'token'));

        return new PushResult(
            false,
            $status,
            mb_substr($error, 0, 190),
            invalidToken: $invalid,
            retryable: ! $invalid && ($status === 429 || $status >= 500 || $status === 401),
        );
    }

    /** @throws ConnectionException|FcmAuthException */
    private function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && ($cached['expires_at'] ?? 0) - self::REFRESH_MARGIN > now()->getTimestamp()) {
            return $cached['token'];
        }

        $credentials = $this->credentials();
        $response = Http::asForm()
            ->timeout((int) config('push.fcm.timeout'))
            ->post($this->tokenUrl(), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->signedJwt($credentials),
            ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new FcmAuthException('توکن دسترسی گوگل رد شد ('.$response->status().'): '.$this->shorten($response->body()));
        }

        $expiresAt = now()->getTimestamp() + (int) ($response->json('expires_in') ?: 3600);
        Cache::put(self::CACHE_KEY, ['token' => $token, 'expires_at' => $expiresAt], max(60, $expiresAt - now()->getTimestamp()));

        return $token;
    }

    private function tokenUrl(): string
    {
        $proxy = config('push.fcm.proxy');

        return $proxy ? rtrim((string) $proxy, '/').'/token' : (string) config('push.fcm.oauth_url');
    }

    private function sendUrl(): string
    {
        $proxy = config('push.fcm.proxy');
        $base = $proxy ? rtrim((string) $proxy, '/') : rtrim((string) config('push.fcm.api_url'), '/');

        return "{$base}/v1/projects/{$this->projectId()}/messages:send";
    }

    /** @return array{client_email: string, private_key: string, project_id: string, token_uri?: string} */
    private function credentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = (string) config('push.fcm.credentials');
        if ($path === '') {
            throw new FcmAuthException('PUSH_FCM_CREDENTIALS در .env تنظیم نشده است.');
        }
        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }
        if (! is_readable($path)) {
            // ویندوز پسوند را پنهان می‌کند؛ «x.json» در عمل «x.json.json» می‌شود (درس بسته‌ی ۰)
            $hint = is_readable($path.'.json') ? ' — فایلی با پسوند دوباره (.json.json) هست؛ نامش را درست کنید.' : '';

            throw new FcmAuthException("فایل حساب سرویس فایربیس پیدا نشد یا خواندنی نیست: {$path}{$hint}");
        }

        $json = json_decode((string) file_get_contents($path), true);
        foreach (['client_email', 'private_key', 'project_id'] as $key) {
            if (! is_array($json) || empty($json[$key])) {
                throw new FcmAuthException("فایل حساب سرویس کلید «{$key}» ندارد (به‌جای کلید حساب سرویس، google-services.json گذاشته شده؟).");
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
                'aud' => $credentials['token_uri'] ?? config('push.fcm.oauth_url'),
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $key = openssl_pkey_get_private($credentials['private_key']);
        if ($key === false || ! openssl_sign(implode('.', $segments), $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new FcmAuthException('کلید خصوصی فایل حساب سرویس خوانده نشد.');
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function shorten(string $text): string
    {
        return mb_strimwidth((string) preg_replace('/\s+/', ' ', $text), 0, 160, '…');
    }
}
