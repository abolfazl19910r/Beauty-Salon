<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotClient;
use App\Services\Bot\BotLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * webhook ربات پلتفرم: POST /api/bot/webhook/{messenger}/{secret}. secret مسیر = BOT_WEBHOOK_SECRET (هر دو پیام‌رسان؛
 * هدر secret تلگرام در بله تضمین نیست). همیشه ۲۰۰ برمی‌گرداند تا پیام‌رسان update را تکرار نکند.
 * دستورها: «/start <کد>» اتصال، «/start» بدون کد راهنما، «/stop» قطع اتصال همین گفت‌وگو.
 */
class BotWebhookController extends Controller
{
    public const HELP = 'برای دریافت اعلان‌های ماهرو، در پنل خودتان (صفحه‌ی پروفایل) دکمه‌ی «اتصال به ربات» را بزنید و لینک را باز کنید، یا کدی را که آنجا می‌بینید این‌جا بفرستید: /start کد';

    public function __invoke(Request $request, string $messenger, string $secret, BotLinkService $links, BotClient $client): JsonResponse
    {
        $expected = (string) config('services.bot.webhook_secret');
        abort_unless($expected !== '' && hash_equals($expected, $secret) && $client->enabled($messenger), 404);

        $message = $request->input('message') ?? $request->input('edited_message');
        $chatId = data_get($message, 'chat.id');
        $text = trim((string) data_get($message, 'text', ''));

        // فقط گفت‌وگوی خصوصی؛ گروه‌ها و کانال‌ها نادیده
        if ($chatId === null || data_get($message, 'chat.type', 'private') !== 'private') {
            return response()->json(['ok' => true]);
        }
        $chatId = (string) $chatId;

        if (preg_match('/^\/start(?:@\S+)?(?:\s+([A-Za-z0-9]{6,32}))?$/u', $text, $m) || preg_match('/^([A-Za-z0-9]{10})$/', $text, $m)) {
            $code = $m[1] ?? null;
            $reply = ! $code
                ? self::HELP
                : ($links->consume($messenger, $chatId, $code)
                    ? 'اتصال انجام شد ✅ از این به بعد اعلان‌های ماهرو این‌جا هم می‌آید. برای قطع اتصال: /stop'
                    : 'این کد معتبر نیست یا منقضی شده است. از پنل خودتان کد تازه بگیرید.');
        } elseif (preg_match('/^\/stop(?:@\S+)?$/u', $text)) {
            $reply = $links->unlinkChat($messenger, $chatId)
                ? 'اتصال قطع شد. دیگر اعلانی این‌جا فرستاده نمی‌شود.'
                : 'این گفت‌وگو به حسابی وصل نیست.';
        } else {
            $reply = self::HELP;
        }

        $client->sendMessage($messenger, $chatId, $reply);

        return response()->json(['ok' => true]);
    }
}
