<?php

namespace App\Services\Notification;

use App\Models\NotificationSetting;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Support\Facades\Cache;

/**
 * The single central point for deciding which channels (SMS/In-App Notification/Telegram Bot or yes) a notification event (e.g. "New Appointment Registration for a
 * Customer") should actually be sent through.
 * Used by both the standard Laravel Notification classes (via the via() method) and direct
 * SMS submissions (e.g. several methods inside BookingObserver that call SMSService::send() directly, not via the
 * Notification system).
 */
class NotificationSettingService
{
    private const CACHE_KEY = 'notification_settings:all';

    /**
     * Informed defaults that align with the "correct" behavior discovered/documented in this project —
     * For example, the recurring SMS upon appointment registration (before payment) and the recurring thank you SMS upon appointment completion are both
     * off by default, but admins can turn them back on from the settings panel.
     */
    private const DEFAULT_OVERRIDES = [
        NotificationEvents::BOOKING_CREATED_CUSTOMER => ['sms_enabled' => false],
        NotificationEvents::BOOKING_COMPLETED_CUSTOMER => ['sms_enabled' => false],
        NotificationEvents::WITHDRAWAL_REQUESTED_ADMIN => ['sms_enabled' => false],
        NotificationEvents::REVIEW_NEGATIVE_ADMIN => ['sms_enabled' => false],
        NotificationEvents::REVIEW_RESPONDED_CUSTOMER => ['sms_enabled' => false],
        NotificationEvents::USER_REGISTERED_ADMIN => ['sms_enabled' => false],
        NotificationEvents::REPORT_EXPORT_READY_ADMIN => ['sms_enabled' => false],
        NotificationEvents::BOOKING_CREATED_ADMIN => ['sms_enabled' => false],
    ];

    public function isEnabled(string $eventKey, string $channel): bool
    {
        $row = $this->resolve($eventKey);

        return match ($channel) {
            'sms' => (bool) $row->sms_enabled,
            'database' => (bool) $row->database_enabled,
            'telegram' => (bool) $row->telegram_enabled,
            default => false,
        };
    }

    /**
     * From the "default channels this notification natively supports" ($base, e.g.
     * ['database','sms']), return only those that are enabled in the current configuration, and if enabled
     * also add 'telegram' (since all events have the potential to be sent via the bot
     * regardless of whether it is listed in $base or not).
     */
    public function channels(string $eventKey, array $base): array
    {
        $channels = [];

        if (in_array('database', $base, true) && $this->isEnabled($eventKey, 'database')) {
            $channels[] = 'database';
        }

        if (in_array('sms', $base, true) && $this->isEnabled($eventKey, 'sms')) {
            $channels[] = 'sms';
        }

        if ($this->isEnabled($eventKey, 'telegram')) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, NotificationSetting>
     */
    public function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => NotificationSetting::all()->keyBy('event_key')->all()
        );
    }

    private function resolve(string $eventKey): NotificationSetting
    {
        $all = $this->all();

        if (isset($all[$eventKey])) {
            return $all[$eventKey];
        }

        $overrides = self::DEFAULT_OVERRIDES[$eventKey] ?? [];

        $row = NotificationSetting::firstOrCreate(
            ['event_key' => $eventKey],
            array_merge([
                'sms_enabled' => true,
                'database_enabled' => true,
                'telegram_enabled' => false,
            ], $overrides)
        );

        $this->flush();

        return $row;
    }
}
