<?php

namespace App\Console\Commands;

use App\Services\Bot\BotClient;
use Illuminate\Console\Command;

/** ثبت یا حذف webhook ربات پلتفرم: php artisan bot:webhook bale | telegram [--delete] */
class BotWebhook extends Command
{
    protected $signature = 'bot:webhook {messenger : bale یا telegram} {--delete : حذف webhook}';

    protected $description = 'ثبت (یا حذف) webhook ربات پلتفرم بله/تلگرام';

    public function handle(BotClient $client): int
    {
        $messenger = (string) $this->argument('messenger');

        if (! $client->enabled($messenger)) {
            $this->error("توکن ربات {$messenger} در .env تنظیم نشده است.");

            return self::FAILURE;
        }

        $secret = (string) config('services.bot.webhook_secret');
        if (! $this->option('delete') && strlen($secret) < 16) {
            $this->error('BOT_WEBHOOK_SECRET را در .env با یک رشته‌ی تصادفی دست‌کم ۱۶ حرفی تنظیم کنید.');

            return self::FAILURE;
        }

        $url = $this->option('delete') ? null : route('bot.webhook', ['messenger' => $messenger, 'secret' => $secret]);
        $result = $client->setWebhook($messenger, $url);

        if (! $result['ok']) {
            $this->error('پاسخ پیام‌رسان: '.json_encode($result['body'], JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $this->info($url ? "webhook {$messenger} ثبت شد: ".preg_replace('#/[^/]+$#', '/***', $url) : "webhook {$messenger} حذف شد.");

        return self::SUCCESS;
    }
}
