<?php

namespace App\Channels;

use App\Services\Bot\BotMessenger;
use Illuminate\Notifications\Notification;

/**
 * کانال «ربات» تنظیمات اعلان (کلید 'telegram'): ربات پلتفرم بله و تلگرام (۲۰۲۶-۱۰-۰۱). پیام فقط به گفت‌وگوهای
 * وصل‌شده‌ی خود گیرنده می‌رود (قبلاً همه‌چیز به یک chat_id سراسری از .env می‌رفت و کانال خاموش بود).
 * متن: toTelegram() اگر اعلان داشته باشد، وگرنه کلید 'message' از toDatabase()/toArray().
 * روشن/خاموش بودن رویداد را خود اعلان با gatedChannels() از پیش سنجیده است.
 */
class TelegramChannel
{
    public function __construct(private readonly BotMessenger $messenger) {}

    public function send($notifiable, Notification $notification): void
    {
        $text = $this->resolveText($notification, $notifiable);
        if (! $text) {
            return;
        }

        $salonId = method_exists($notification, 'notificationSalonId') ? $notification->notificationSalonId($notifiable) : null;

        $this->messenger->send($notifiable, $text, null, $salonId);
    }

    private function resolveText(Notification $notification, $notifiable): ?string
    {
        foreach (['toTelegram', 'toDatabase', 'toArray'] as $method) {
            if (method_exists($notification, $method)) {
                $data = $notification->{$method}($notifiable);

                return is_string($data) ? $data : ($data['message'] ?? null);
            }
        }

        return null;
    }
}
