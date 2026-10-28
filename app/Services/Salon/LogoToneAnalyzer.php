<?php

namespace App\Services\Salon;

/**
 * ⭐ بسته‌ی د — خوانایی لوگو (۲۰۲۶-۱۰-۱۵).
 *
 * روشنایی پیکسل‌های غیرشفاف لوگو را با GD می‌سنجد و می‌گوید لوگو روی کدام پس‌زمینه گم می‌شود:
 * - light: بیشتر «جوهر» لوگو روی پس‌زمینه‌ی روشن ماهرو کنتراست کم دارد (لوگوی سفید/طلایی روشن) ← روی تم روشن داخل پلاک تیره.
 * - dark:  بیشتر جوهر روی پس‌زمینه‌ی تیره گم می‌شود (لوگوی مشکی/قهوه‌ای تیره) ← روی تم تیره داخل پلاک روشن.
 * - mid:   روی هر دو خواناست، یا تصویر پس‌زمینه‌ی کامل خودش را دارد (JPG/PNG بدون شفافیت) ← هرگز پلاک نمی‌خورد.
 *
 * رنگ خود لوگو هیچ‌وقت عوض نمی‌شود؛ فقط پشتش پلاک می‌آید. اگر GD فایل را نخواند (یا افزونه نباشد) null برمی‌گردد = بدون پلاک.
 */
class LogoToneAnalyzer
{
    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const MID = 'mid';

    /** پس‌زمینه‌های سیستم طراحی (partials/mahru-theme: --m-bg تیره و روشن) */
    public const DARK_BG = [21, 17, 14];

    public const LIGHT_BG = [251, 248, 242];

    /** پیکسلی با کنتراست کمتر از این روی یک پس‌زمینه «گم‌شده» حساب می‌شود */
    private const MIN_CONTRAST = 2.0;

    /** اگر دست‌کم این سهم از جوهر لوگو روی یک پس‌زمینه گم شود، آن تم پلاک می‌خواهد */
    private const LOST_SHARE = 0.5;

    /** پیکسل‌های کم‌رنگ‌تر از این (لبه‌ی نرم، سایه) در سنجش نمی‌آیند */
    private const MIN_OPACITY = 0.2;

    /** تصویری که این سهم از پیکسل‌هایش کاملاً مات است پس‌زمینه‌ی خودش را دارد */
    private const OPAQUE_SHARE = 0.97;

    private const SAMPLE = 96;

    public function analyzeFile(string $path): ?string
    {
        if (! is_file($path) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $bytes = @file_get_contents($path);

        return $bytes === false ? null : $this->analyzeBytes($bytes);
    }

    public function analyzeBytes(string $bytes): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        return $this->classify($this->sample($source));
    }

    /** کوچک‌کردن تا ۹۶ پیکسل (سنجش سریع، حتی برای لوگوی ۲ مگابایتی) با حفظ شفافیت */
    private function sample(\GdImage $source): \GdImage
    {
        if (! imageistruecolor($source)) {
            imagepalettetotruecolor($source);
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1, self::SAMPLE / max($w, $h, 1));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $sample = imagecreatetruecolor($tw, $th);
        imagealphablending($sample, false);
        imagesavealpha($sample, true);
        imagefill($sample, 0, 0, imagecolorallocatealpha($sample, 0, 0, 0, 127));
        imagecopyresampled($sample, $source, 0, 0, 0, 0, $tw, $th, $w, $h);

        return $sample;
    }

    private function classify(\GdImage $img): string
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $darkBg = self::luminance(self::DARK_BG);
        $lightBg = self::luminance(self::LIGHT_BG);

        $pixels = 0;
        $opaque = 0;
        $ink = 0.0;
        $lostOnDark = 0.0;
        $lostOnLight = 0.0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $opacity = 1 - (($c >> 24) & 0x7F) / 127;
                $pixels++;
                if ($opacity >= 0.95) {
                    $opaque++;
                }
                if ($opacity < self::MIN_OPACITY) {
                    continue;
                }

                $l = self::luminance([($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF]);
                $ink += $opacity;
                if (self::contrast($l, $darkBg) < self::MIN_CONTRAST) {
                    $lostOnDark += $opacity;
                }
                if (self::contrast($l, $lightBg) < self::MIN_CONTRAST) {
                    $lostOnLight += $opacity;
                }
            }
        }

        if ($ink <= 0 || $opaque / max(1, $pixels) >= self::OPAQUE_SHARE) {
            return self::MID;
        }
        if ($lostOnDark / $ink >= self::LOST_SHARE) {
            return self::DARK;
        }
        if ($lostOnLight / $ink >= self::LOST_SHARE) {
            return self::LIGHT;
        }

        return self::MID;
    }

    /** روشنایی نسبی WCAG 2 برای رنگ sRGB */
    public static function luminance(array $rgb): float
    {
        $lin = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    public static function contrast(float $a, float $b): float
    {
        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
