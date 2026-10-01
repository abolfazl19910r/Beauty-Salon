<?php

namespace App\Services\Bot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bot API بله و تلگرام (هر دو یک قالب‌اند، فقط دامنه فرق دارد: services.bale.api_base / services.telegram.api_base).
 * ⚠️ از سرور داخل ایران api.telegram.org در دسترس نیست — تلگرام فقط با سرور بیرون یا TELEGRAM_API_BASE (پراکسی).
 */
class BotClient
{
    public const OK = 'ok';

    public const GONE = 'gone';       // ربات بلاک شده یا گفت‌وگو دیگر نیست → اتصال حذف شود

    public const FAILED = 'failed';   // خطای گذرا → job دوباره تلاش کند

    public function enabled(string $messenger): bool
    {
        return in_array($messenger, \App\Models\BotLink::MESSENGERS, true) && (bool) config("services.{$messenger}.bot_token");
    }

    /** @return list<string> */
    public function enabledMessengers(): array
    {
        return array_values(array_filter(\App\Models\BotLink::MESSENGERS, fn ($m) => $this->enabled($m)));
    }

    public function username(string $messenger): ?string
    {
        return config("services.{$messenger}.username") ?: null;
    }

    public function deepLink(string $messenger, string $code): ?string
    {
        $username = $this->username($messenger);
        if (! $username) {
            return null;
        }

        return ($messenger === 'bale' ? 'https://ble.ir/' : 'https://t.me/').ltrim($username, '@').'?start='.$code;
    }

    public function sendMessage(string $messenger, string $chatId, string $text): string
    {
        if (! $this->enabled($messenger)) {
            return self::FAILED;
        }

        try {
            $response = Http::timeout(10)->post($this->url($messenger, 'sendMessage'), ['chat_id' => $chatId, 'text' => $text]);
        } catch (\Throwable $e) {
            Log::warning("Bot sendMessage failed ({$messenger})", ['error' => $e->getMessage()]);

            return self::FAILED;
        }

        if ($response->successful() && $response->json('ok') !== false) {
            return self::OK;
        }

        // 403: کاربر ربات را بلاک کرد؛ 400 «chat not found»: گفت‌وگو دیگر نیست
        if ($response->status() === 403 || ($response->status() === 400 && str_contains(strtolower((string) $response->json('description')), 'chat not found'))) {
            return self::GONE;
        }

        Log::warning("Bot sendMessage rejected ({$messenger})", ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);

        return self::FAILED;
    }

    /**
     * long polling (bot:poll، محیط محلی بدون آدرس عمومی). تا وقتی webhook ثبت است پیام‌رسان getUpdates را رد می‌کند.
     *
     * @return array{ok: bool, result: array, description: ?string}
     */
    public function getUpdates(string $messenger, int $offset, int $timeout = 25): array
    {
        try {
            $response = Http::timeout($timeout + 10)->post($this->url($messenger, 'getUpdates'), [
                'offset' => $offset, 'timeout' => $timeout, 'allowed_updates' => ['message', 'edited_message'],
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'result' => [], 'description' => $e->getMessage()];
        }

        return [
            'ok' => $response->successful() && $response->json('ok') !== false,
            'result' => (array) ($response->json('result') ?? []),
            'description' => $response->json('description'),
        ];
    }

    public function setWebhook(string $messenger, ?string $url): array
    {
        $response = Http::timeout(15)->post($this->url($messenger, $url ? 'setWebhook' : 'deleteWebhook'), $url ? ['url' => $url] : []);

        return ['ok' => $response->successful() && $response->json('ok') !== false, 'body' => $response->json() ?? $response->body()];
    }

    private function url(string $messenger, string $method): string
    {
        return rtrim((string) config("services.{$messenger}.api_base"), '/').'/bot'.config("services.{$messenger}.bot_token").'/'.$method;
    }
}
