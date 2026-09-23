<?php

namespace App\Support;

/**
 * ⭐ پوشه‌ی جدای هر سالن روی دیسک public (۲۰۲۶-۰۹-۲۴).
 *
 * قبلاً همه‌ی سالن‌ها فایل‌هاشون رو در پوشه‌های مشترک (gallery/، services/، categories/، blog/)
 * می‌ریختن. جداسازی نمایش همون موقع هم درست بود (هر رکورد BelongsToSalon داره و هر سالن فقط
 * رکوردهای خودش رو می‌بینه)، ولی «فضای مصرفی گالری» کل فایل‌های همه‌ی سالن‌ها رو می‌شمرد و پاک‌کردن/
 * بکاپ/انتقال فایل‌های یک سالن عملاً ممکن نبود. از این به بعد هر آپلود جدید در
 * salons/{salon_id}/{kind}/ ذخیره می‌شه. فایل‌های قدیمی جابه‌جا نمی‌شن — مسیرشون در دیتابیس
 * ذخیره‌ست و همچنان درست کار می‌کنن.
 */
class SalonStorage
{
    /**
     * @param  string  $kind  gallery | services | categories | blog | branding
     */
    public static function directory(?int $salonId, string $kind): string
    {
        // بدون سالن مشخص (نباید در مسیرهای ادمین پیش بیاد) همون پوشه‌ی مشترک قدیمی.
        return $salonId ? "salons/{$salonId}/{$kind}" : $kind;
    }

    public static function forCurrentSalon(string $kind): string
    {
        return self::directory(app(CurrentSalon::class)->id(), $kind);
    }
}
