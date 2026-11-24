<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Hekmatinasser\Verta\Verta;

/**
 * نمایش فارسی رقم، مبلغ و تاریخ (بسته‌ی ۰ به‌روزرسانی ظاهری، ۲۰۲۶-۱۱-۲۴).
 *
 * - رقم‌ها همیشه فارسی؛ جداکننده‌ی هزارگان «٬».
 * - عدد علامت‌دار داخل LRI…PDI می‌رود تا در متن راست‌به‌چپ علامت از عدد جدا نشود و سمت چپ بماند.
 * - تاریخ امسال «سه‌شنبه ۳ آذر»، سال‌های دیگر «۳ آذر ۱۴۰۴»؛ با ساعت «…، ۱۴:۳۰».
 *
 * فقط برای متن نمایشی؛ مقدار input، data-*، Excel/CSV و JSON اپ‌ها لاتین می‌مانند.
 */
final class Fa
{
    private const LATIN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const PERSIAN = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public const THOUSANDS = '٬';

    public const MINUS = '−';

    private const LRI = "\u{2066}";

    private const PDI = "\u{2069}";

    /** نام روزهای verta («سه شنبه»، «پنج شنبه») با نیم‌فاصله */
    private const WEEKDAYS = ['سه شنبه' => 'سه‌شنبه', 'پنج شنبه' => 'پنج‌شنبه'];

    public static function digits(mixed $value): string
    {
        return str_replace([...self::LATIN, ...self::ARABIC], [...self::PERSIAN, ...self::PERSIAN], (string) $value);
    }

    /** رقم فارسی ← لاتین (برای خواندن ورودی کاربر) */
    public static function latin(mixed $value): string
    {
        return str_replace([...self::PERSIAN, ...self::ARABIC], [...self::LATIN, ...self::LATIN], (string) $value);
    }

    /**
     * عدد با جداکننده‌ی «٬» و رقم فارسی؛ منفی با «−» و، اگر $signed، مثبت با «+».
     */
    public static function number(int|float|string|null $value, int $decimals = 0, bool $signed = false): string
    {
        $number = (float) ($value ?? 0);
        $formatted = number_format(abs($number), $decimals, '.', ',');
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        $formatted = str_replace([',', '.'], [self::THOUSANDS, '٫'], self::digits($formatted));

        $isZero = round($number, $decimals) == 0;
        if ($number < 0 && ! $isZero) {
            return self::LRI.self::MINUS.$formatted.self::PDI;
        }
        if ($signed && $number > 0 && ! $isZero) {
            return self::LRI.'+'.$formatted.self::PDI;
        }

        return $formatted;
    }

    /** مبلغ تومان؛ $unit = true ← «… تومان» */
    public static function money(int|float|string|null $amount, bool $unit = false, bool $signed = false): string
    {
        return self::number($amount, 0, $signed).($unit ? ' تومان' : '');
    }

    /** «۲۵٪» */
    public static function percent(int|float|string|null $value, int $decimals = 0): string
    {
        return self::number($value, $decimals).'٪';
    }

    /**
     * تاریخ خوانا: امسال «سه‌شنبه ۳ آذر»، سال‌های دیگر «۳ آذر ۱۴۰۴»؛ با $withTime «…، ۱۴:۳۰».
     */
    public static function date(DateTimeInterface|string|null $date, bool $withTime = false, bool $weekday = true): string
    {
        $v = self::verta($date);
        if (! $v) {
            return '—';
        }

        $sameYear = $v->year === Verta::now()->year;
        $text = $sameYear
            ? ($weekday ? $v->format('l').' ' : '').$v->format('j F')
            : $v->format('j F Y');
        $text = strtr($text, self::WEEKDAYS);

        if ($withTime) {
            $text .= '، '.$v->format('H:i');
        }

        return self::digits($text);
    }

    /** «۱۴:۳۰» */
    public static function time(DateTimeInterface|string|null $date): string
    {
        $v = self::verta($date);

        return $v ? self::digits($v->format('H:i')) : '—';
    }

    /** تاریخ فشرده با قالب verta (پیش‌فرض «۱۴۰۵/۰۹/۰۳») و رقم فارسی — برای جای تنگ و فیلترها */
    public static function jdate(DateTimeInterface|string|null $date, string $format = 'Y/m/d'): string
    {
        $v = self::verta($date);

        return $v ? self::digits(strtr($v->format($format), self::WEEKDAYS)) : '—';
    }

    private static function verta(DateTimeInterface|string|null $date): ?Verta
    {
        if ($date === null || $date === '') {
            return null;
        }

        return $date instanceof Verta ? $date : new Verta($date instanceof CarbonInterface ? $date : $date);
    }
}
