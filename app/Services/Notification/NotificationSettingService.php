<?php

namespace App\Services\Notification;

use App\Models\NotificationSetting;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Support\Facades\Cache;

/**
 * تنها نقطه‌ی مرکزی تصمیم‌گیری برای اینکه یک رویداد اطلاع‌رسانی (مثلاً «ثبت نوبت جدید برای
 * مشتری») واقعاً از چه کانال‌هایی (پیامک/نوتیفیکیشن داخل‌برنامه‌ای/ربات تلگرام یا بله) ارسال شود.
 * هم توسط کلاس‌های Notification استاندارد لاراول (از طریق متد via()) و هم توسط ارسال‌های مستقیم
 * SMS (مثل چند متد داخل BookingObserver که مستقیماً SMSService::send() را صدا می‌زنند، نه از طریق
 * سیستم Notification) استفاده می‌شود.
 */
class NotificationSettingService
{
    private const CACHE_KEY = 'notification_settings:all';

    /**
     * پیش‌فرض‌های آگاهانه‌ای که با رفتار «درست»ی که در این پروژه کشف/مستند شده هم‌راستا هستن —
     * مثلاً پیامک تکراری زمان ثبت نوبت (قبل از پرداخت) و پیامک تشکر تکراری زمان تکمیل نوبت هر دو
     * به‌صورت پیش‌فرض خاموش هستن، ولی ادمین می‌تونه از پنل تنظیمات دوباره روشنشون کنه.
     */
    private const DEFAULT_OVERRIDES = [
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
     * از میان کانال‌های «پیش‌فرضی که این نوتیفیکیشن ذاتاً پشتیبانی می‌کند» ($base، مثلاً
     * ['database','sms'])، فقط آن‌هایی که در تنظیمات فعلی فعال هستند را برمی‌گرداند و در صورت فعال
     * بودن، 'telegram' را هم اضافه می‌کند (چون همه‌ی رویدادها بالقوه قابلیت ارسال از طریق ربات را
     * دارند، صرف‌نظر از اینکه در $base ذکر شده باشد یا نه).
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