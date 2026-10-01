<?php

namespace App\Services\Bot;

/**
 * پردازش یک update پیام‌رسان (بله/تلگرام) — مشترک بین webhook (سرور) و bot:poll (محیط محلی بدون آدرس عمومی).
 * دستورها: «/start <کد>» یا خود کد = اتصال، «/start» بدون کد = راهنما، «/stop» = قطع اتصال همین گفت‌وگو.
 * فقط گفت‌وگوی خصوصی؛ گروه و کانال نادیده.
 */
class BotUpdateHandler
{
    public const HELP = 'برای دریافت اعلان‌های ماهرو، در پنل خودتان (صفحه‌ی پروفایل) دکمه‌ی «اتصال به ربات» را بزنید و لینک را باز کنید، یا کدی را که آنجا می‌بینید این‌جا بفرستید: /start کد';

    public function __construct(private readonly BotLinkService $links, private readonly BotClient $client) {}

    public function handle(string $messenger, array $update): void
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        $chatId = data_get($message, 'chat.id');
        $text = trim((string) data_get($message, 'text', ''));

        if ($chatId === null || data_get($message, 'chat.type', 'private') !== 'private') {
            return;
        }
        $chatId = (string) $chatId;

        if (preg_match('/^\/start(?:@\S+)?(?:\s+([A-Za-z0-9]{6,32}))?$/u', $text, $m) || preg_match('/^([A-Za-z0-9]{10})$/', $text, $m)) {
            $code = $m[1] ?? null;
            $reply = ! $code
                ? self::HELP
                : ($this->links->consume($messenger, $chatId, $code)
                    ? 'اتصال انجام شد ✅ از این به بعد اعلان‌های ماهرو این‌جا هم می‌آید. برای قطع اتصال: /stop'
                    : 'این کد معتبر نیست یا منقضی شده است. از پنل خودتان کد تازه بگیرید.');
        } elseif (preg_match('/^\/stop(?:@\S+)?$/u', $text)) {
            $reply = $this->links->unlinkChat($messenger, $chatId)
                ? 'اتصال قطع شد. دیگر اعلانی این‌جا فرستاده نمی‌شود.'
                : 'این گفت‌وگو به حسابی وصل نیست.';
        } else {
            $reply = self::HELP;
        }

        $this->client->sendMessage($messenger, $chatId, $reply);
    }
}
