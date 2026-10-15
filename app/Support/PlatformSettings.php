<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات پلتفرم که سوپرادمین از «تنظیمات پلتفرم» عوض می‌کند (۲۰۲۶-۱۰-۱۴). ترتیب: مقدار ذخیره‌شده در جدول platform_settings ←
 * پیش‌فرض config (که از .env می‌آید، مثل BRAND_SUPPORT_PHONE). خالی گذاشتن یک فیلد در پنل = برگشت به پیش‌فرض .env.
 * همه با هم در یک کلید cache (صفحه‌ی فروش در هر بازدید می‌خواندشان).
 */
final class PlatformSettings
{
    private const CACHE_KEY = 'platform_settings:all';

    /** کلید → [برچسب، پیش‌فرض config، نوع اعتبارسنجی] */
    public const FIELDS = [
        'support_phone' => ['label' => 'تلفن پشتیبانی (صفحه‌ی فروش)', 'config' => 'brand.support_phone', 'type' => 'phone'],
        'public_email' => ['label' => 'ایمیل عمومی (صفحه‌ی فروش)', 'config' => 'brand.email', 'type' => 'email'],
        'instagram_url' => ['label' => 'اینستاگرام', 'config' => 'brand.instagram_url', 'type' => 'url'],
        'linkedin_url' => ['label' => 'لینکدین', 'config' => 'brand.linkedin_url', 'type' => 'url'],
        'telegram_url' => ['label' => 'کانال تلگرام', 'config' => 'brand.telegram_url', 'type' => 'url'],
        'bale_url' => ['label' => 'کانال بله', 'config' => 'brand.bale_url', 'type' => 'url'],
        'demo_notify_email' => ['label' => 'ایمیل گیرنده‌ی درخواست‌های دمو', 'config' => 'brand.demo_notify_email', 'type' => 'email'],
    ];

    public static function get(string $key): ?string
    {
        if (! array_key_exists($key, self::FIELDS)) {
            throw new \InvalidArgumentException("Unknown platform setting [{$key}]");
        }

        $stored = self::stored()[$key] ?? null;
        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        $fallback = config(self::FIELDS[$key]['config']);

        return $fallback !== null && $fallback !== '' ? (string) $fallback : null;
    }

    /** فقط مقدارهای ذخیره‌شده (برای فرم پنل؛ خالی = پیش‌فرض .env) */
    public static function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            // پیش از اجرای migration (نصب تازه) صفحه‌ی فروش نباید بشکند
            if (! Schema::hasTable('platform_settings')) {
                return [];
            }

            return PlatformSetting::query()->pluck('value', 'key')->all();
        });
    }

    /**
     * تنظیم سطح پلتفرم بیرون از فرم راه‌های تماس (حالت پوش، توکن ربات؛ بسته‌ی ۴). null = حذف و برگشت به پیش‌فرض.
     */
    public static function raw(string $key): ?string
    {
        $value = self::stored()[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    public static function put(string $key, ?string $value): void
    {
        $value = $value === null ? null : trim($value);
        PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, ?string> $values */
    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::FIELDS)) {
                continue;
            }
            $value = $value === null ? null : trim($value);
            PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
