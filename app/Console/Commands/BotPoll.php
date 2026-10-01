<?php

namespace App\Console\Commands;

use App\Services\Bot\BotClient;
use App\Services\Bot\BotUpdateHandler;
use Illuminate\Console\Command;

/**
 * دریافت پیام‌های ربات با long polling به‌جای webhook — برای محیط محلی (php artisan serve) که آدرس عمومی HTTPS ندارد و
 * پیام‌رسان نمی‌تواند webhook را صدا بزند. همان پردازش webhook (BotUpdateHandler). روی سرور لازم نیست.
 * پیش از اجرا webhook آن پیام‌رسان نباید ثبت باشد: php artisan bot:webhook bale --delete
 */
class BotPoll extends Command
{
    protected $signature = 'bot:poll {messenger=bale : bale یا telegram} {--once : فقط یک دور (برای تست)} {--timeout=25 : ثانیه‌ی انتظار هر دور}';

    protected $description = 'دریافت پیام‌های ربات با polling (محیط محلی بدون webhook)';

    public function handle(BotClient $client, BotUpdateHandler $handler): int
    {
        $messenger = (string) $this->argument('messenger');

        if (! $client->enabled($messenger)) {
            $this->error("توکن ربات {$messenger} در .env تنظیم نشده است.");

            return self::FAILURE;
        }

        $this->info("در انتظار پیام‌های ربات {$messenger}… (توقف: Ctrl+C)");
        $offset = 0;

        do {
            $result = $client->getUpdates($messenger, $offset, $this->option('once') ? 0 : (int) $this->option('timeout'));

            if (! $result['ok']) {
                $description = (string) $result['description'];
                $this->error('پاسخ پیام‌رسان: '.$description);
                if (str_contains(strtolower($description), 'webhook')) {
                    $this->line("webhook ثبت است؛ اول اجرا کنید: php artisan bot:webhook {$messenger} --delete");
                }

                return self::FAILURE;
            }

            foreach ($result['result'] as $update) {
                $offset = max($offset, (int) ($update['update_id'] ?? 0) + 1);
                $handler->handle($messenger, $update);
                $this->line('پیام از گفت‌وگوی '.data_get($update, 'message.chat.id', '?').': '.data_get($update, 'message.text', ''));
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
