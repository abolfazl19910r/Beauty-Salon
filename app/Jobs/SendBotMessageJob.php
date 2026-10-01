<?php

namespace App\Jobs;

use App\Models\BotLink;
use App\Services\Bot\BotClient;
use App\Support\Queues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * یک پیام ربات به یک گفت‌وگوی وصل‌شده (صف sms، مثل پیامک). ربات بلاک شده یا گفت‌وگو حذف شده → اتصال پاک می‌شود.
 * خطای گذرا → تا سه بار با فاصله.
 */
class SendBotMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 20;

    public array $backoff = [10, 60];

    public function __construct(public readonly int $botLinkId, public readonly string $text)
    {
        $this->onQueue(Queues::SMS);
    }

    public function handle(BotClient $client): void
    {
        $link = BotLink::find($this->botLinkId);
        if (! $link) {
            return;
        }

        $result = $client->sendMessage($link->messenger, $link->chat_id, $this->text);

        if ($result === BotClient::GONE) {
            $link->delete();

            return;
        }

        if ($result === BotClient::FAILED && $this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 60);
        }
    }
}
