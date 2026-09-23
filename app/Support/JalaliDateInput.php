<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;
use Throwable;

/**
 * ⭐ ورودی تاریخ شمسی فیلترها (۲۰۲۶-۰۹-۲۴) — «۱۴۰۵/۰۷/۰۱» یا «1405/7/1» → Carbon میلادی.
 * نامعتبر = null (فیلتر نادیده گرفته می‌شه، نه خطای ۵۰۰).
 */
class JalaliDateInput
{
    public static function toCarbon(?string $value, bool $endOfDay = false): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '-' => '/',
        ]);

        if (! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $value, $m)) {
            return null;
        }

        // Jalalian::fromFormat ماه/روز تک‌رقمی (۱۴۰۵/۷/۱) رو قبول نمی‌کنه.
        $value = sprintf('%04d/%02d/%02d', $m[1], $m[2], $m[3]);

        try {
            $date = Carbon::instance(Jalalian::fromFormat('Y/m/d', $value)->toCarbon());
        } catch (Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    public static function isValid(?string $value): bool
    {
        return $value === null || trim($value) === '' || self::toCarbon($value) !== null;
    }

    /** اولین لحظه‌ی ماه شمسی جاری. */
    public static function startOfCurrentJalaliMonth(): Carbon
    {
        $now = Jalalian::now();

        return self::toCarbon(sprintf('%04d/%02d/01', $now->getYear(), $now->getMonth()));
    }
}
