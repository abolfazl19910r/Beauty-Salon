<?php

namespace App\Support\Sms;

use App\Models\SalonSmsTemplate;
use Illuminate\Support\Facades\Cache;

/**
 * متن‌های اختصاصی یک سالن (۲۰۲۶-۱۰-۱۴). پیامک‌ها اغلب در صف و بدون سالن جاری فرستاده می‌شوند، پس سالن همیشه صریح است
 * (salon_id خود نوبت یا سالن) و scope سراسری BelongsToSalon عمداً کنار گذاشته می‌شود — شرط salon_id همین‌جاست.
 */
final class SmsTemplateStore
{
    private const CACHE_PREFIX = 'sms_templates:salon:';

    /** متن سالن یا پیش‌فرض */
    public static function body(?int $salonId, string $key): string
    {
        if ($salonId !== null) {
            $custom = self::overrides($salonId)[$key] ?? null;
            if ($custom !== null) {
                return $custom;
            }
        }

        return SmsTemplates::get($key)['default'];
    }

    /** @return array<string, string> */
    public static function overrides(int $salonId): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$salonId, fn () => SalonSmsTemplate::withoutGlobalScope('salon')
            ->where('salon_id', $salonId)
            ->pluck('body', 'key')
            ->all());
    }

    public static function save(int $salonId, string $key, string $body): void
    {
        SalonSmsTemplate::withoutGlobalScope('salon')->updateOrCreate(['salon_id' => $salonId, 'key' => $key], ['body' => $body]);
        Cache::forget(self::CACHE_PREFIX.$salonId);
    }

    public static function reset(int $salonId, string $key): void
    {
        SalonSmsTemplate::withoutGlobalScope('salon')->where('salon_id', $salonId)->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$salonId);
    }

    public static function render(?int $salonId, string $key, array $vars): string
    {
        return SmsTemplates::fill(self::body($salonId, $key), $vars);
    }
}
