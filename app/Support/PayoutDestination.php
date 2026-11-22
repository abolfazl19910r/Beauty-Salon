<?php

namespace App\Support;

/**
 * مقصد واریز تسویه‌ی درگاه پلتفرم به سالن (۲۰۲۶-۱۱-۲۲): شبا، شماره‌ی کارت یا شماره‌ی حساب. فقط ارقام لاتین نگه داشته می‌شود
 * (رقم فارسی/عربی، فاصله، خط تیره و «IR» شبا حذف) و برای نمایش جز ابرمدیر، فقط چهار رقم آخر دیده می‌شود.
 */
final class PayoutDestination
{
    public const METHODS = ['iban' => 'شماره‌ی شبا', 'card' => 'شماره‌ی کارت', 'account' => 'شماره‌ی حساب'];

    public static function digits(?string $value): string
    {
        $latin = strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return (string) preg_replace('/\D+/', '', $latin);
    }

    /** رقم‌های فارسی/عربی به لاتین، بقیه‌ی متن دست‌نخورده (برای درصد اعشاری) */
    public static function digitsAndDot(string $value): string
    {
        return trim(strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
    }

    /** شبای کامل با IR، یا null اگر نامعتبر */
    public static function iban(?string $value): ?string
    {
        $digits = self::digits($value);

        return strlen($digits) === Iban::DIGITS && Iban::isValid(Iban::COUNTRY.$digits) ? Iban::COUNTRY.$digits : null;
    }

    /** کارت ۱۶ رقمی با رقم کنترلی درست (Luhn) */
    public static function card(?string $value): ?string
    {
        $digits = self::digits($value);
        if (strlen($digits) !== 16) {
            return null;
        }

        $sum = 0;
        foreach (str_split(strrev($digits)) as $i => $d) {
            $n = (int) $d * ($i % 2 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return $sum % 10 === 0 ? $digits : null;
    }

    /** شماره‌ی حساب: ۶ تا ۲۰ رقم (قالب هر بانک فرق دارد) */
    public static function account(?string $value): ?string
    {
        $digits = self::digits($value);

        return strlen($digits) >= 6 && strlen($digits) <= 20 ? $digits : null;
    }

    public static function mask(?string $value): string
    {
        $value = (string) $value;

        return $value === '' ? '—' : '…'.substr($value, -4);
    }

    public static function label(?string $method): string
    {
        return self::METHODS[$method] ?? (string) $method;
    }
}
