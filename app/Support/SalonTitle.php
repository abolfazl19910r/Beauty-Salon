<?php

namespace App\Support;

/**
 * عنوان نمایشی سالن در متن‌های عمومی سایت (بسته‌ی «ح»، ۱۰، ۲۰۲۶-۱۰-۱۹).
 *
 * پیش‌تر «سالن زیبایی {نام}» ثابت بود؛ وقتی نام خودش «سالن زیبایی راستا» است، «درباره ما» و پانویس «سالن زیبایی سالن زیبایی
 * راستا» می‌شد. نامی که خودش نوع کسب‌وکار را می‌گوید دست نمی‌خورد.
 */
final class SalonTitle
{
    /** نام‌هایی با این آغاز پیشوند نمی‌گیرند */
    private const OWN_PREFIXES = ['سالن', 'آرایشگاه', 'کلینیک', 'استودیو', 'آتلیه', 'مرکز', 'خانه'];

    public static function for(string $name): string
    {
        $name = trim($name);
        $normalized = str_replace(["\u{200C}", 'ي', 'ك'], [' ', 'ی', 'ک'], $name);

        foreach (self::OWN_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return $name;
            }
        }

        return "سالن زیبایی {$name}";
    }
}
