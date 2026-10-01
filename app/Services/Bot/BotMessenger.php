<?php

namespace App\Services\Bot;

use App\Jobs\SendBotMessageJob;
use App\Models\BotLink;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Notification\NotificationSettingService;

/**
 * پیام ربات به گفت‌وگوهای وصل‌شده‌ی یک کاربر (یا کاربرِ یک متخصص). هر پیام یک job روی صف sms. اگر رویداد داده شود،
 * فقط وقتی «کانال ربات» آن رویداد در تنظیمات اعلان همان سالن روشن است.
 */
class BotMessenger
{
    public function __construct(private readonly BotClient $client) {}

    /** @return list<int> */
    public function userIdsFor(?object $notifiable): array
    {
        if ($notifiable instanceof User) {
            return [$notifiable->id];
        }

        if ($notifiable instanceof Specialist) {
            $userId = $notifiable->user_id
                ?? User::where('phone', $notifiable->phone)->where('user_type', '!=', 'customer')->value('id');

            return $userId ? [(int) $userId] : [];
        }

        return [];
    }

    public function hasLink(?object $notifiable): bool
    {
        $userIds = $this->userIdsFor($notifiable);
        $messengers = $this->client->enabledMessengers();

        return $userIds !== [] && $messengers !== []
            && BotLink::whereIn('user_id', $userIds)->whereIn('messenger', $messengers)->exists();
    }

    /** کانال ربات را فقط برای گیرنده‌ای نگه می‌دارد که بله/تلگرام را وصل کرده (بدون آن job خالی صف نمی‌شود). */
    public function onlyIfLinked(array $channels, ?object $notifiable): array
    {
        if (in_array('telegram', $channels, true) && ! $this->hasLink($notifiable)) {
            return array_values(array_diff($channels, ['telegram']));
        }

        return $channels;
    }

    public function send(?object $notifiable, string $text, ?string $eventKey = null, ?int $salonId = null): int
    {
        $text = trim($text);
        $userIds = $this->userIdsFor($notifiable);

        if ($text === '' || $userIds === [] || $this->client->enabledMessengers() === []) {
            return 0;
        }

        if ($eventKey !== null && ! app(NotificationSettingService::class)->isEnabled($eventKey, 'telegram', $salonId)) {
            return 0;
        }

        $salonName = $salonId ? Salon::whereKey($salonId)->value('name') : null;
        $body = $salonName ? "«{$salonName}»\n{$text}" : $text;

        $links = BotLink::whereIn('user_id', $userIds)->whereIn('messenger', $this->client->enabledMessengers())->get();
        foreach ($links as $link) {
            SendBotMessageJob::dispatch($link->id, $body);
        }

        return $links->count();
    }
}
