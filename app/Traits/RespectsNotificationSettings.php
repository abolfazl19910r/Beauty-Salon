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
     * تنظیمات مال سالنی است که اعلان درباره‌ی آن است (۲۰۲۶-۰۹-۳۰): اول سالنِ خودِ رکورد (settingsSalonId())، بعد سالنِ
     * گیرنده، بعد CurrentSalon. اعلان‌ها اغلب در صف ساخته می‌شوند که CurrentSalon ندارد، و گیرنده‌ای که مالک چند سالن
     * است سالن یکتایی ندارد — پس سالن رکورد مقدم است.
     */
    protected function gatedChannels(string $eventKey, array $base, ?object $notifiable = null): array
    {
        $channels = app(NotificationSettingService::class)->channels(
            $eventKey,
            $base,
            $this->notificationSalonId($notifiable)
        );

        // کانال ربات فقط وقتی گیرنده واقعاً بله/تلگرام را وصل کرده — ۲۰۲۶-۱۰-۰۱
        return app(\App\Services\Bot\BotMessenger::class)->onlyIfLinked($channels, $notifiable);
    }

    /** سالنی که اعلان درباره‌ی آن است؛ برای تنظیمات اعلان و برای ستون salon_id اعلان داخلی. */
    public function notificationSalonId(?object $notifiable = null): ?int
    {
        return $this->settingsSalonId() ?? SalonOfNotifiable::resolve($notifiable) ?? app(CurrentSalon::class)->id();
    }

    /** سالنِ رکوردی که اعلان درباره‌ی آن است (نوبت، برداشت، نظر، …)؛ null یعنی از گیرنده پیدا شود. */
    protected function settingsSalonId(): ?int
    {
        return null;
    }
}
