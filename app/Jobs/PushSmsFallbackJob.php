<?php

namespace App\Jobs;

use App\Models\PushMessage;
use App\Services\Push\AppMessenger;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * «بدون رسید → پیامک» (تصمیم ۲، گزینه‌ی «ج»): با تأخیر برابر مهلت دسته‌ی پیام صف می‌شود؛ اگر تا آن موقع هیچ دستگاهی رسید
 * نفرستاده بود، همان متن پیامک ذخیره‌شده از سهمیه‌ی سالن می‌رود. اجرای دوباره یا هم‌زمان پیامک دوم نمی‌فرستد (AppMessenger::fallbackNow).
 */
class PushSmsFallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 20;

    public function __construct(public readonly string $messageId)
    {
        $this->onQueue(Queues::SMS);
    }

    public function salonId(): ?int
    {
        $salonId = PushMessage::query()->whereKey($this->messageId)->value('salon_id');

        return $salonId ? (int) $salonId : null;
    }

    public function handle(AppMessenger $messenger): void
    {
        $message = PushMessage::query()->find($this->messageId);
        if (! $message || $message->acked_at || $message->sms_sent_at) {
            return;
        }

        $messenger->fallbackNow($message, 'no_receipt');
    }
}
