<?php

namespace App\Services\Pwa;

use App\Models\Salon;
use Illuminate\Support\Facades\Storage;

/**
 * آیکون صفحه‌ی اصلی گوشی برای سایت سالن (بسته‌ی ۶): لوگوی سالن (اگر پلن «لوگوی اختصاصی» دارد) وسط یک مربع با رنگ زمینه‌ی روشن
 * پالت سایت؛ آیفون گوشه‌ها را خودش گرد می‌کند. maskable (اندروید): لوگو در ناحیه‌ی امن ۶۰٪. بی‌لوگو یا لوگوی SVG/خراب ← آیکون ماهرو.
 * هر اندازه یک بار با GD ساخته و روی دیسک public نگه داشته می‌شود؛ کلید = لوگو + زمان تغییرش + رنگ، پس عوض کردن لوگو آیکون تازه می‌سازد.
 */
class SalonPwaIcon
{
    public const VARIANTS = ['180' => [180, false], '192' => [192, false], '512' => [512, false], 'maskable-512' => [512, true]];

    /** مسیر مطلق فایل PNG آماده */
    public function path(Salon $salon, string $variant, string $background): string
    {
        [$size, $maskable] = self::VARIANTS[$variant];
        $logo = $this->logoFile($salon);

        if ($logo === null) {
            return $this->fallback($size, $maskable);
        }

        $disk = Storage::disk('public');
        $key = substr(md5($salon->logo_path.'|'.@filemtime($logo).'|'.$background.'|v1'), 0, 16);
        $relative = "pwa-icons/{$salon->id}/{$key}-{$variant}.png";

        if (! $disk->exists($relative)) {
            $png = $this->render($logo, $size, $maskable, $background);
            if ($png === null) {
                return $this->fallback($size, $maskable);
            }
            $disk->put($relative, $png);
        }

        return $disk->path($relative);
    }

    private function logoFile(Salon $salon): ?string
    {
        if ($salon->logoUrl() === null || ! $salon->logo_path) {
            return null;
        }
        $ext = strtolower(pathinfo($salon->logo_path, PATHINFO_EXTENSION));
        $disk = Storage::disk('public');

        return in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true) && $disk->exists($salon->logo_path) ? $disk->path($salon->logo_path) : null;
    }

    private function render(string $logoPath, int $size, bool $maskable, string $background): ?string
    {
        $src = @imagecreatefromstring((string) file_get_contents($logoPath));
        if ($src === false) {
            return null;
        }

        $canvas = imagecreatetruecolor($size, $size);
        [$r, $g, $b] = sscanf(ltrim($background, '#'), '%02x%02x%02x');
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, (int) $r, (int) $g, (int) $b));
        imagealphablending($canvas, true);

        $box = (int) round($size * ($maskable ? 0.6 : 0.78));
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min($box / max(1, $w), $box / max(1, $h));
        $dw = max(1, (int) round($w * $scale));
        $dh = max(1, (int) round($h * $scale));
        imagecopyresampled($canvas, $src, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $w, $h);

        ob_start();
        imagepng($canvas, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($canvas);

        return $png;
    }

    private function fallback(int $size, bool $maskable): string
    {
        return public_path(match (true) {
            $maskable => 'brand/icon-maskable-512.png',
            $size <= 180 => 'apple-touch-icon.png',
            $size <= 192 => 'brand/icon-192.png',
            default => 'brand/icon-512.png',
        });
    }
}
