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

    /**
     * ⭐ اطلاعات حقوقی و اینماد (بسته‌ی مجوز 5د): «تماس با ما» (تلفن ثابت، ایمیل و نشانی پستی؛ الزام اینماد)، هویت صاحب پلتفرم در
     * قوانین (ماده‌ی ۳۳ قانون تجارت الکترونیکی) و کد HTML لوگوی اینماد که عیناً روی صفحه‌ی اصلی می‌آید. پیش‌فرض .env ندارند.
     */
    public const LEGAL_FIELDS = [
        'legal_name' => ['label' => 'نام صاحب امتیاز (شخص یا شرکت، همان نام شناسنامه‌ی اینماد)', 'type' => 'text', 'max' => 190],
        'national_id' => ['label' => 'شناسه‌ی ملی شرکت (اختیاری؛ فقط اگر شخص حقوقی است)', 'type' => 'text', 'max' => 20],
        'landline_phone' => ['label' => 'تلفن ثابت (الزام اینماد)', 'type' => 'phone', 'max' => 20],
        'contact_email' => ['label' => 'ایمیل تماس (الزام اینماد؛ خالی = ایمیل عمومی بالا)', 'type' => 'email', 'max' => 190],
        'postal_address' => ['label' => 'نشانی پستی (الزام اینماد)', 'type' => 'textarea', 'max' => 500],
        'postal_code' => ['label' => 'کد پستی', 'type' => 'postal', 'max' => 10],
        'enamad_badge_html' => ['label' => 'کد HTML لوگوی اینماد (عیناً از پنل اینماد، بخش «دسترسی‌ها و امکانات»)', 'type' => 'html', 'max' => 5000],
    ];

    public static function get(string $key): ?string
    {
        if (array_key_exists($key, self::LEGAL_FIELDS)) {
            $value = self::raw($key);

            return $key === 'contact_email' ? ($value ?? self::get('public_email')) : $value;
        }

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
            if (! array_key_exists($key, self::FIELDS) && ! array_key_exists($key, self::LEGAL_FIELDS)) {
                continue;
            }
            $value = $value === null ? null : trim($value);
            PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
