<?php

namespace App\Traits;

use App\Services\Notification\NotificationSettingService;

/**
 * Used on Notification classes to make via() follow the
 * admin-editable settings (the "Notification Settings" page) instead of returning a fixed array of channels.
 */
trait RespectsNotificationSettings
{
    protected function gatedChannels(string $eventKey, array $base): array
    {
        return app(NotificationSettingService::class)->channels($eventKey, $base);
    }
}
