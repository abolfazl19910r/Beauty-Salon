<?php

namespace App\Services\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * ntfy خودمیزبان (تصمیم ۲۰۲۶-۱۰-۰۲؛ نتیجه‌ی بسته‌ی ۰: در قطعی اینترنت بین‌الملل تنها پوشی که می‌رسد). سمت سرور آماده است؛
 * دستگاه ntfy فقط وقتی ثبت می‌شود که PUSH_NTFY_SERVER تنظیم باشد و اپ اتصال دائمی را داشته باشد (بعد از سرور مجازی داخل ایران).
 * «توکن» دستگاه ntfy نام تاپیک تصادفی آن است. انتشار JSON (عنوان فارسی در هدر HTTP جا نمی‌شود — درس بسته‌ی ۰).
 */
class NtfyClient
{
    public function configured(): bool
    {
        return (bool) config('push.ntfy.server');
    }

    /** @param array<string, string> $data */
    public function send(string $topic, string $title, string $body, array $data, PushUrgency $urgency): PushResult
    {
        $request = Http::timeout((int) config('push.ntfy.timeout'));
        if ($token = config('push.ntfy.token')) {
            $request = $request->withToken((string) $token);
        }

        try {
            $response = $request->post(rtrim((string) config('push.ntfy.server'), '/'), [
                'topic' => $topic,
                'title' => $title,
                // شناسه‌ی پیام برای رسید اپ در خط آخر متن (ntfy فیلد داده‌ی دلخواه ندارد)
                'message' => $body."\n[m:".($data['message_id'] ?? '').']',
                'priority' => $urgency === PushUrgency::Info ? 3 : 5,
            ]);
        } catch (ConnectionException $e) {
            return PushResult::outage('ntfy: '.mb_strimwidth($e->getMessage(), 0, 150, '…'));
        }

        if ($response->successful()) {
            return PushResult::accepted($response->status());
        }

        return new PushResult(false, $response->status(), mb_substr('ntfy '.$response->status().' '.$response->body(), 0, 190),
            retryable: $response->status() === 429 || $response->status() >= 500);
    }
}
