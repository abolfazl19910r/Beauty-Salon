<?php

namespace App\Services\Notification;

use App\Models\NotificationSetting;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use App\Support\CurrentSalon;
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
    public function __construct(private readonly NotificationSettingRepositoryInterface $notificationSettingRepository) {}

    private const CACHE_KEY = 'notification_settings:salon:';

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

    /**
     * $salonId: سالنی که تنظیماتش ملاک است؛ پیش‌فرض CurrentSalon. اعلان‌های صف‌شده سالن را از گیرنده می‌گیرند
     * (RespectsNotificationSettings) چون در صف CurrentSalon ست نیست.
     */
    public function isEnabled(string $eventKey, string $channel, ?int $salonId = null): bool
    {
        $row = $this->resolve($eventKey, $salonId ?? app(CurrentSalon::class)->id());

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
    public function channels(string $eventKey, array $base, ?int $salonId = null): array
    {
        $salonId ??= app(CurrentSalon::class)->id();

        $channels = [];

        if (in_array('database', $base, true) && $this->isEnabled($eventKey, 'database', $salonId)) {
            $channels[] = 'database';
        }

        if (in_array('sms', $base, true) && $this->isEnabled($eventKey, 'sms', $salonId)) {
            $channels[] = 'sms';
        }

        if ($this->isEnabled($eventKey, 'telegram', $salonId)) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    public function flush(?int $salonId = null): void
    {
        $salonId ??= app(CurrentSalon::class)->id();
        Cache::forget(self::CACHE_KEY.($salonId ?? 'none'));
    }

    /**
     * @return array<string, NotificationSetting>
     */
    public function all(?int $salonId = null): array
    {
        $salonId ??= app(CurrentSalon::class)->id();

        return Cache::rememberForever(
            self::CACHE_KEY.($salonId ?? 'none'),
            fn () => $this->notificationSettingRepository->getAllKeyedByEventKey($salonId)
        );
    }

    private function resolve(string $eventKey, ?int $salonId): NotificationSetting
    {
        $all = $this->all($salonId);

        if (isset($all[$eventKey])) {
            return $all[$eventKey];
        }

        $overrides = self::DEFAULT_OVERRIDES[$eventKey] ?? [];

        $row = $this->notificationSettingRepository->firstOrCreateForEvent(
            $eventKey,
            array_merge([
                'sms_enabled' => true,
                'database_enabled' => true,
                'telegram_enabled' => false,
            ], $overrides),
            $salonId
        );

        $this->flush($salonId);

        return $row;
    }
}
