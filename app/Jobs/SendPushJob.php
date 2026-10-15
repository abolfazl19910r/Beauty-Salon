<?php

namespace App\Jobs;

use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\NtfyClient;
use App\Services\Push\PushMode;
use App\Services\Push\PushResult;
use App\Services\Push\PushUrgency;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * ارسال یک پیام اپ به همه‌ی دستگاه‌های گیرنده (صف push، بسته‌ی ۴).
 *
 * درس بسته‌ی ۰: شکست DNS/اتصال به گوگل گم شدن پیام نیست — با فاصله دوباره امتحان می‌شود (تا عمر پیام)، و پیام فوری/عادی در
 * حالت «فعال» همان لحظه پیامک می‌شود (قطعی؛ تصمیم ۲). توکن باطل (UNREGISTERED) دستگاه را غیرفعال می‌کند.
 */
class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 6;

    public int $timeout = 60;

    /** فاصله‌ی تلاش‌های دوباره (ثانیه) */
    public const BACKOFF = [15, 45, 120, 300, 900];

    public function __construct(public readonly string $messageId)
    {
        $this->onQueue(Queues::PUSH);
    }

    public function salonId(): ?int
    {
        $salonId = PushMessage::query()->whereKey($this->messageId)->value('salon_id');

        return $salonId ? (int) $salonId : null;
    }

    public function handle(FcmClient $fcm, NtfyClient $ntfy, AppMessenger $messenger): void
    {
        $message = PushMessage::query()->find($this->messageId);
        if (! $message) {
            return;
        }

        $urgency = PushUrgency::tryFrom($message->urgency) ?? PushUrgency::Normal;
        $data = $this->payload($message);
        $pending = PushDelivery::query()->with('device')
            ->where('push_message_id', $message->id)
            ->whereIn('status', [PushDelivery::QUEUED, PushDelivery::FAILED])
            ->get();

        $retry = false;
        $outage = false;

        foreach ($pending as $delivery) {
            $device = $delivery->device;
            if (! $device || $device->disabled_at) {
                $delivery->forceFill(['status' => PushDelivery::INVALID, 'error' => 'device disabled'])->save();

                continue;
            }

            $result = match ($device->provider) {
                PushDevice::PROVIDER_NTFY => $ntfy->send($device->token, $message->title, $message->body, $data, $urgency),
                default => $fcm->send($device->token, $message->title, $message->body, $data, $urgency),
            };

            $this->record($delivery, $device, $result);
            $retry = $retry || (! $result->ok && $result->retryable);
            $outage = $outage || $result->outage;
        }

        if ($outage && $message->mode === PushMode::LIVE && $urgency !== PushUrgency::Info) {
            $messenger->fallbackNow($message, 'outage');
        }

        // تا عمر پیام دوباره امتحان؛ بعد از آن پیام برای گوشی بی‌ارزش است (FCM هم نگهش نمی‌داشت)
        $expired = $message->created_at && $message->created_at->lt(now()->subSeconds($urgency->ttlSeconds()));
        if ($retry && ! $expired && $this->attempts() < $this->tries) {
            $this->release(self::BACKOFF[min($this->attempts() - 1, count(self::BACKOFF) - 1)] ?? 300);
        }
    }

    private function record(PushDelivery $delivery, PushDevice $device, PushResult $result): void
    {
        if ($result->ok) {
            $delivery->forceFill(['status' => PushDelivery::ACCEPTED, 'accepted_at' => now(), 'error' => null, 'attempts' => $delivery->attempts + 1])->save();

            return;
        }

        if ($result->invalidToken) {
            $delivery->forceFill(['status' => PushDelivery::INVALID, 'error' => $result->error, 'attempts' => $delivery->attempts + 1])->save();
            $device->disable('invalid_token');

            return;
        }

        $delivery->forceFill(['status' => PushDelivery::FAILED, 'error' => $result->error, 'attempts' => $delivery->attempts + 1])->save();
        Log::warning('Push send failed', ['push_message_id' => $delivery->push_message_id, 'device' => $device->id, 'status' => $result->status, 'error' => $result->error]);
    }

    /** @return array<string, string> داده‌ی پیام (همه رشته؛ قاعده‌ی FCM) — اپ با آن رسید می‌فرستد و صفحه‌ی درست را باز می‌کند */
    private function payload(PushMessage $message): array
    {
        $data = ['message_id' => (string) $message->id, 'type' => (string) $message->event_key, 'urgency' => (string) $message->urgency];
        foreach ((array) $message->data as $key => $value) {
            if ($value !== null && is_scalar($value)) {
                $data[(string) $key] = (string) $value;
            }
        }
        if ($message->salon_id) {
            $data['salon_id'] = (string) $message->salon_id;
        }

        return $data;
    }
}
