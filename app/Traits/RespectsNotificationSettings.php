<?php

namespace App\Traits;

use App\Services\Notification\NotificationSettingService;
use App\Support\CurrentSalon;
use App\Support\SalonOfNotifiable;

/**
 * Used on Notification classes to make via() follow the
 * admin-editable settings (the "Notification Settings" page) instead of returning a fixed array of channels.
 */
trait RespectsNotificationSettings
{
    /**
     * تنظیمات مال سالنِ گیرنده است (تصمیم ۲۰۲۶-۰۹-۲۷)؛ اعلان‌ها اغلب در صف ساخته می‌شوند که CurrentSalon ندارد، پس
     * سالن از خود گیرنده خوانده می‌شود و فقط اگر معلوم نبود به CurrentSalon برمی‌گردد.
     */
    protected function gatedChannels(string $eventKey, array $base, ?object $notifiable = null): array
    {
        return app(NotificationSettingService::class)->channels(
            $eventKey,
            $base,
            SalonOfNotifiable::resolve($notifiable) ?? app(CurrentSalon::class)->id()
        );
    }
}
