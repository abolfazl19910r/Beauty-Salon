<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotClient;
use App\Services\Bot\BotUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * webhook ربات پلتفرم: POST /api/bot/webhook/{messenger}/{secret}. secret مسیر = BOT_WEBHOOK_SECRET (هر دو پیام‌رسان؛
 * هدر secret تلگرام در بله تضمین نیست). همیشه ۲۰۰ برمی‌گرداند تا پیام‌رسان update را تکرار نکند. پردازش:
 * BotUpdateHandler (همان که bot:poll در محیط محلی استفاده می‌کند).
 */
class BotWebhookController extends Controller
{
    public function __invoke(Request $request, string $messenger, string $secret, BotClient $client, BotUpdateHandler $handler): JsonResponse
    {
        $expected = (string) config('services.bot.webhook_secret');
        abort_unless($expected !== '' && hash_equals($expected, $secret) && $client->enabled($messenger), 404);

        $handler->handle($messenger, $request->all());

        return response()->json(['ok' => true]);
    }
}
