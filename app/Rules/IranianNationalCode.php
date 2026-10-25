<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * کد ملی ۱۰ رقمی با رقم کنترلی (بسته‌ی مجوز 5د). رقم‌های فارسی/عربی پیش از بررسی لاتین می‌شوند (از فرم فارسی).
 * الگوریتم: s = Σ d[i]·(10−i) برای ۹ رقم اول، r = s mod 11؛ رقم کنترلی = r اگر r < 2، وگرنه 11 − r. ده رقم یکسان نامعتبر.
 */
class IranianNationalCode implements ValidationRule
{
    public static function normalize(?string $value): string
    {
        return strtr(trim((string) $value), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '-' => '', ' ' => '']);
    }

    public static function isValid(string $code): bool
    {
        if (! preg_match('/^[0-9]{10}$/', $code) || preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        $r = $sum % 11;
        $check = (int) $code[9];

        return $r < 2 ? $check === $r : $check === 11 - $r;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid(self::normalize(is_scalar($value) ? (string) $value : ''))) {
            $fail('کد ملی معتبر نیست (۱۰ رقم با رقم کنترلی درست).');
        }
    }
}
