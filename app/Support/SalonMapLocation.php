<?php

namespace App\Support;

use App\Models\Salon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * مکان سالن روی نقشه (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): مالک سالن در «اطلاعات سالن» (اختیاری) لینک اشتراک‌گذاری
 * گوگل‌مپ/نشان/بلد — کوتاه یا کامل — یا خود مختصات را می‌چسباند.
 *
 * آزمون ویندوز (تصمیم ابوالفضل): لینک کوتاه گوگل‌مپ (maps.app.goo.gl/…) هم پذیرفته می‌شود؛ خود لینک در map_url
 * می‌ماند و «مسیریابی» همان را باز می‌کند. مختصات اگر در لینک بود، یا سرور توانست لینک کوتاه را در چند ثانیه باز کند
 * (سرور ایران شاید به گوگل نرسد)، در map_lat/map_lng هم ذخیره می‌شود. بی لینک و بی مختصات دکمه‌ی «مسیریابی» نیست.
 */
final class SalonMapLocation
{
    /** محدوده‌ی تقریبی ایران؛ مختصات بیرون از آن به احتمال زیاد اشتباه است (یا عرض و طول جابه‌جا) */
    private const LAT = [24.0, 40.5];

    private const LNG = [43.5, 63.5];

    /** میزبان‌های نقشه که لینکشان پذیرفته می‌شود (و تنها میزبان‌هایی که سرور برای باز کردن لینک کوتاه به آن‌ها درخواست می‌دهد) */
    private const MAP_HOSTS = '/^(?:(?:www\.|maps\.)?google\.[a-z.]{2,6}|maps\.app\.goo\.gl|goo\.gl|(?:www\.)?nshn\.ir|(?:www\.)?neshan\.org|(?:www\.)?balad\.ir)$/i';

    private const RESOLVE_TIMEOUT = 4;

    private const MAX_REDIRECTS = 5;

    /**
     * @return array{0: float, 1: float}|null [عرض، طول] یا null وقتی متن مختصاتی ندارد
     */
    public static function parse(?string $input): ?array
    {
        $text = trim(strtr(rawurldecode((string) $input), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '،' => ',',
        ]));
        if ($text === '') {
            return null;
        }

        $num = '(-?\d{1,3}(?:\.\d+)?)';
        $patterns = [
            '/!3d'.$num.'!4d'.$num.'/',                                                // google.com/maps/place/…/data=…!3dlat!4dlng (خود نقطه، دقیق‌تر از @)
            '/[?&](?:lat|latitude)='.$num.'.*?[?&](?:lng|lon|longitude)='.$num.'/i', // nshn.ir/?lat=&lng= ، balad.ir/location?latitude=&longitude=
            '/[?&](?:q|query|ll|destination|center)='.$num.',\s*\+?'.$num.'/i',        // google.com/maps?q=lat,lng
            '/\/(?:place|search|share|dir\/[^\/]*)\/'.$num.',\s*\+?'.$num.'/i',        // google.com/maps/place/lat,lng ، neshan.org/maps/share/lat,lng
            '/@'.$num.','.$num.'/',                                                    // google.com/maps/@lat,lng ، neshan.org/maps/@lat,lng
            '/^'.$num.'\s*[,\s]\s*'.$num.'$/',                                          // «35.7, 51.4»
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return self::normalize((float) $m[1], (float) $m[2]);
            }
        }

        return null;
    }

    /** لینک https یکی از نقشه‌ها (گوگل‌مپ، نشان، بلد) — یا null وقتی متن لینک نقشه نیست */
    public static function mapLink(?string $input): ?string
    {
        $url = trim((string) $input);
        if ($url === '' || ! preg_match('#^https?://#i', $url) || preg_match('/\s/', $url) || mb_strlen($url) > 500) {
            return null;
        }
        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        if (! preg_match(self::MAP_HOSTS, $host)) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '');
        // google.com و goo.gl فقط بخش نقشه؛ نه جستجو یا لینک کوتاه دیگری
        if (preg_match('/google\./i', $host) && ! preg_match('/^maps\./i', $host) && ! str_starts_with($path, '/maps')) {
            return null;
        }
        if (strcasecmp($host, 'goo.gl') === 0 && ! str_starts_with($path, '/maps')) {
            return null;
        }

        return preg_replace('#^http://#i', 'https://', $url);
    }

    /** پیام خطای فرم برای متنی که نه لینک نقشه است نه مختصات درست، یا null وقتی درست است */
    public static function error(?string $input): ?string
    {
        if (trim((string) $input) === '' || self::mapLink($input) !== null) {
            return null;
        }
        $point = self::parse($input);
        if ($point === null) {
            return 'این متن لینک نقشه یا مختصات نیست؛ لینک اشتراک‌گذاری گوگل‌مپ، نشان یا بلد، یا خود مختصات (عرض، طول) را بچسبانید.';
        }
        if (! self::inIran(...$point)) {
            return 'این مختصات در ایران نیست؛ عرض و طول جغرافیایی را بررسی کنید.';
        }

        return null;
    }

    /**
     * ستون‌های ذخیره برای مقدار فرم (بعد از error() درست).
     *
     * @return array{map_url: string|null, map_lat: float|null, map_lng: float|null}
     */
    public static function attributesFor(?string $input): array
    {
        $link = self::mapLink($input);
        $point = $link !== null ? (self::parse($link) ?? self::resolve($link)) : self::parse($input);
        if ($point !== null && ! self::inIran(...$point)) {
            $point = null;
        }

        return ['map_url' => $link, 'map_lat' => $point[0] ?? null, 'map_lng' => $point[1] ?? null];
    }

    /**
     * لینک کوتاه را با دنبال کردن ریدایرکت‌ها (فقط روی میزبان‌های نقشه، چند ثانیه) باز می‌کند و مختصات مقصد را می‌خواند.
     * هر خطا، مهلت تمام‌شده یا نرسیدن سرور به گوگل → null (لینک بی مختصات ذخیره می‌شود).
     *
     * @return array{0: float, 1: float}|null
     */
    public static function resolve(string $url): ?array
    {
        try {
            for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
                if (self::mapLink($url) === null) {
                    return null;
                }
                $response = Http::withoutRedirecting()
                    ->timeout(self::RESOLVE_TIMEOUT)
                    ->connectTimeout(self::RESOLVE_TIMEOUT)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MahruBot/1.0)'])
                    ->get($url);
                $location = $response->header('Location');
                if (! $response->redirect() || $location === '') {
                    return null;
                }
                if (str_starts_with($location, '/')) {
                    $parts = parse_url($url);
                    $location = 'https://'.$parts['host'].$location;
                }
                if ($point = self::parse($location)) {
                    return $point;
                }
                $url = $location;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /** لینک دکمه‌ی «مسیریابی»: لینکی که مالک سالن داده، وگرنه نقطه‌ی «نشان» از مختصات — یا null */
    public static function directionsUrl(Salon $salon): ?string
    {
        return $salon->map_url ?: self::neshanUrl($salon);
    }

    /** لینک نقطه‌ی «نشان» (در اندروید اپ نشان را باز می‌کند و جای دیگر نسخه‌ی وب را) — یا null بی مختصات */
    public static function neshanUrl(Salon $salon): ?string
    {
        if ($salon->map_lat === null || $salon->map_lng === null) {
            return null;
        }

        return 'https://nshn.ir/?lat='.self::fmt((float) $salon->map_lat).'&lng='.self::fmt((float) $salon->map_lng);
    }

    /** مقدار فیلد فرم: لینک ذخیره‌شده، وگرنه «35.7000000,51.4000000» */
    public static function forInput(Salon $salon): string
    {
        if ($salon->map_url) {
            return $salon->map_url;
        }

        return $salon->map_lat === null ? '' : self::fmt((float) $salon->map_lat).','.self::fmt((float) $salon->map_lng);
    }

    /** مختصات ذخیره‌شده برای نمایش زیر فیلد («35.76, 51.406») — یا null */
    public static function coordinates(Salon $salon): ?string
    {
        return $salon->map_lat === null ? null : self::fmt((float) $salon->map_lat).', '.self::fmt((float) $salon->map_lng);
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
