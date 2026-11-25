<?php

namespace App\Support;

use App\Models\Salon;

/**
 * مکان سالن روی نقشه (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): «نشان» لینک جستجوی نشانی ندارد و فقط لینک نقطه با مختصات می‌سازد،
 * پس مالک سالن در «اطلاعات سالن» (اختیاری) لینک اشتراک‌گذاری نشان/گوگل‌مپ/بلد یا خود مختصات را می‌چسباند.
 * بی مختصات دکمه‌ی «مسیریابی» نشان داده نمی‌شود (تصمیم ابوالفضل).
 */
final class SalonMapLocation
{
    /** محدوده‌ی تقریبی ایران؛ مختصات بیرون از آن به احتمال زیاد اشتباه است (یا عرض و طول جابه‌جا) */
    private const LAT = [24.0, 40.5];

    private const LNG = [43.5, 63.5];

    /**
     * @return array{0: float, 1: float}|null [عرض، طول] یا null وقتی متن مختصاتی ندارد
     */
    public static function parse(?string $input): ?array
    {
        $text = trim(strtr((string) $input, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '،' => ',', '%2C' => ',', '%2c' => ',',
        ]));
        if ($text === '') {
            return null;
        }

        $num = '(-?\d{1,3}(?:\.\d+)?)';
        $patterns = [
            '/[?&](?:lat|latitude)='.$num.'.*?[?&](?:lng|lon|longitude)='.$num.'/i', // nshn.ir/?lat=&lng= ، balad.ir/location?latitude=&longitude=
            '/@'.$num.','.$num.'/',                                                   // google.com/maps/@lat,lng ، neshan.org/maps/@lat,lng
            '/[?&](?:q|query|ll|destination)='.$num.','.$num.'/i',                    // google.com/maps?q=lat,lng
            '/\/share\/'.$num.','.$num.'/i',                                           // neshan.org/maps/share/lat,lng
            '/^'.$num.'\s*[,\s]\s*'.$num.'$/',                                         // «35.7, 51.4»
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return self::normalize((float) $m[1], (float) $m[2]);
            }
        }

        return null;
    }

    /** پیام خطای فرم برای متنی که مختصات قابل‌خواندن ندارد، یا null وقتی درست است */
    public static function error(?string $input): ?string
    {
        if (trim((string) $input) === '') {
            return null;
        }
        if (preg_match('#(nshn\.ir|goo\.gl|maps\.app\.goo\.gl|balad\.ir/p)/[A-Za-z0-9_-]+/?$#i', trim((string) $input)) && self::parse($input) === null) {
            return 'لینک کوتاه را نمی‌توان خواند؛ در نقشه روی «اشتراک‌گذاری» بزنید و لینک کامل یا خود مختصات (مثلاً ۳۵٫۷۰۰۰، ۵۱٫۴۰۰۰) را بچسبانید.';
        }
        $point = self::parse($input);
        if ($point === null) {
            return 'از این متن مختصات پیدا نشد؛ لینک اشتراک‌گذاری نشان یا گوگل‌مپ یا خود مختصات (عرض، طول) را بچسبانید.';
        }
        if (! self::inIran(...$point)) {
            return 'این مختصات در ایران نیست؛ عرض و طول جغرافیایی را بررسی کنید.';
        }

        return null;
    }

    /** لینک نقطه‌ی «نشان» (در اندروید اپ نشان را باز می‌کند و جای دیگر نسخه‌ی وب را) — یا null بی مختصات */
    public static function neshanUrl(Salon $salon): ?string
    {
        if ($salon->map_lat === null || $salon->map_lng === null) {
            return null;
        }

        return 'https://nshn.ir/?lat='.self::fmt((float) $salon->map_lat).'&lng='.self::fmt((float) $salon->map_lng);
    }

    /** مقدار فیلد فرم («35.7000000,51.4000000») */
    public static function forInput(Salon $salon): string
    {
        return $salon->map_lat === null ? '' : self::fmt((float) $salon->map_lat).','.self::fmt((float) $salon->map_lng);
    }

    /** @return array{0: float, 1: float} */
    private static function normalize(float $lat, float $lng): array
    {
        // جابه‌جایی رایج عرض و طول (۵۱٫۴، ۳۵٫۷) را درست کن
        if (! self::inIran($lat, $lng) && self::inIran($lng, $lat)) {
            [$lat, $lng] = [$lng, $lat];
        }

        return [round($lat, 7), round($lng, 7)];
    }

    private static function inIran(float $lat, float $lng): bool
    {
        return $lat >= self::LAT[0] && $lat <= self::LAT[1] && $lng >= self::LNG[0] && $lng <= self::LNG[1];
    }

    private static function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 7, '.', ''), '0'), '.');
    }
}
