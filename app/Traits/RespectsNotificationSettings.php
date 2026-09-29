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
        return app(NotificationSettingService::class)->channels(
            $eventKey,
            $base,
            $this->settingsSalonId() ?? SalonOfNotifiable::resolve($notifiable) ?? app(CurrentSalon::class)->id()
        );
    }

    /** سالنِ رکوردی که اعلان درباره‌ی آن است (نوبت، برداشت، نظر، …)؛ null یعنی از گیرنده پیدا شود. */
    protected function settingsSalonId(): ?int
    {
        return null;
    }
}
