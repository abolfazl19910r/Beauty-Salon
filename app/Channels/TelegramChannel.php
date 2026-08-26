<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bot channel — for events for which the admin has enabled the "Bot" channel from the notification settings page
 * . Telegram and Yes both have compatible Bot APIs (only the domain is different), so the message
 * will be sent to both (whichever is configured in .env).
 *
 * Message content: If the Notification class itself implements the toTelegram() method, it will be used
 * ; otherwise, the 'message' key in the toDatabase()/toArray() output of the same
 * class will be used automatically — since almost all Notification classes in the project already have such a key, this
 * fallback will generate a reasonable and ready-made text without having to manipulate each class individually.
 */
class TelegramChannel
{
    public function send($notifiable, Notification $notification)
    {
        $text = $this->resolveText($notification, $notifiable);

        if (! $text) {
            return;
        }

        $this->sendVia('telegram', $text);
        $this->sendVia('bale', $text);
    }

    private function resolveText(Notification $notification, $notifiable): ?string
    {
        if (method_exists($notification, 'toTelegram')) {
            return $notification->toTelegram($notifiable);
        }

        if (method_exists($notification, 'toDatabase')) {
            $data = $notification->toDatabase($notifiable);

            return $data['message'] ?? null;
        }

        if (method_exists($notification, 'toArray')) {
            $data = $notification->toArray($notifiable);

            return $data['message'] ?? null;
        }

        return null;
    }

    private function sendVia(string $bot, string $text): void
    {
        $token = config("services.{$bot}.bot_token");
        $chatId = config("services.{$bot}.chat_id");
        $apiBase = config("services.{$bot}.api_base");

        if (! $token || ! $chatId) {
            return;
        }

        try {
            Http::timeout(10)->post("{$apiBase}/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning("❌ خطا در ارسال پیام از طریق ربات ({$bot})", ['error' => $e->getMessage()]);
        }
    }
}
