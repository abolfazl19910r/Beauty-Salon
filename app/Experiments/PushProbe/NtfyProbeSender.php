<?php

namespace App\Experiments\PushProbe;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * راه بدون گوگل برای دوره‌های قطعی اینترنت بین‌الملل: سرور ntfy خودمیزبان. گوشی با اپ رسمی ntfy مشترک تاپیک است و
 * برای هر سروری غیر از ntfy.sh خودش یک اتصال دائمی نگه می‌دارد («instant delivery»)؛ FCM در کار نیست.
 * انتشار به شکل JSON است چون عنوان فارسی در هدر HTTP جا نمی‌شود.
 */
class NtfyProbeSender
{
    public function server(): string
    {
        $server = (string) config('push_probe.ntfy.server');
        if ($server === '') {
            throw new RuntimeException('PUSH_PROBE_NTFY_SERVER در .env تنظیم نشده است (مثلاً http://127.0.0.1:8090).');
        }

        return rtrim($server, '/');
    }

    public function send(string $topic, ProbeMessage $message): ProbeResult
    {
        $started = hrtime(true);

        try {
            $response = $this->client()->post($this->server(), [
                'topic' => $topic,
                'title' => $message->title(),
                'message' => $message->body(),
                // ۵ = فوری (صدا و لرزش بلند)؛ هم‌ارز priority HIGH در FCM
                'priority' => 5,
                'tags' => ['bell'],
            ]);
        } catch (ConnectionException $e) {
            return new ProbeResult(false, null, $this->elapsed($started), 'اتصال برقرار نشد: '.$e->getMessage());
        }

        if ($response->successful()) {
            return new ProbeResult(true, $response->status(), $this->elapsed($started), (string) $response->json('id'));
        }

        return new ProbeResult(false, $response->status(), $this->elapsed($started), mb_strimwidth((string) $response->body(), 0, 200, '…'));
    }

    public function health(): ProbeResult
    {
        $started = hrtime(true);

        try {
            $response = $this->client()->get($this->server().'/v1/health');
        } catch (ConnectionException $e) {
            return new ProbeResult(false, null, $this->elapsed($started), 'اتصال برقرار نشد: '.$e->getMessage());
        }

        $healthy = $response->json('healthy') === true;

        return new ProbeResult($healthy, $response->status(), $this->elapsed($started), $healthy ? 'سالم' : mb_strimwidth($response->body(), 0, 200, '…'));
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout((int) config('push_probe.timeout'))->acceptJson();
        $token = (string) config('push_probe.ntfy.token');

        return $token !== '' ? $client->withToken($token) : $client;
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
