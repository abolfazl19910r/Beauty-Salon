<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotClient;
use App\Services\Bot\BotLinkService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * دکمه‌های «اتصال به ربات» / «قطع اتصال» در پروفایل مدیر، متخصص و مشتری. اتصال: کد یک‌بارمصرف ۱۰ دقیقه‌ای ساخته و در
 * session (bot_connect) برگردانده می‌شود؛ صفحه لینک مستقیم ربات و خود کد را نشان می‌دهد.
 */
class BotConnectController extends Controller
{
    public function store(Request $request, BotLinkService $links, BotClient $client): RedirectResponse
    {
        $messenger = (string) $request->route('messenger');
        abort_unless($client->enabled($messenger), 404);

        $code = $links->createCode($request->user(), app(CurrentSalon::class)->id());

        return back()->with('bot_connect', [
            'messenger' => $messenger,
            'code' => $code,
            'url' => $client->deepLink($messenger, $code),
            'minutes' => BotLinkService::CODE_TTL_MINUTES,
        ]);
    }

    public function destroy(Request $request, BotLinkService $links): RedirectResponse
    {
        $links->unlinkUser($request->user(), (string) $request->route('messenger'));

        return back()->with('success', 'اتصال ربات قطع شد.');
    }
}
