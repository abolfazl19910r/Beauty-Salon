<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Services\Pwa\SalonPwaIcon;
use App\Support\CurrentSalon;
use App\Support\SalonTitle;
use App\Support\Themes\SalonAppearance;
use App\Support\Themes\ThemeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PWA (بسته‌ی ۶، ۲۰۲۶-۱۰-۲۰) — آیفون اپ ندارد (اپ‌استور برای توسعه‌دهنده‌ی ایرانی بسته است)؛ سایت سالن و پنل متخصص با «افزودن به
 * صفحه‌ی اصلی» مثل اپ باز می‌شوند. manifest هر سالن (نام، رنگ پالت، آیکون از لوگو) روی آدرس خود سالن؛ manifest پنل متخصص روی دامنه‌ی
 * اصلی با scope «/» تا ورود (/login) هم داخل اپ نصب‌شده بماند (آیفون صفحه‌ی بیرون از scope را با کوکی جدا باز می‌کند).
 * service worker فقط فایل‌های ثابت و صفحه‌ی «اینترنت قطع است» را نگه می‌دارد؛ هیچ صفحه‌ی واردشده، API یا فرمی کش نمی‌شود.
 */
class PwaController extends Controller
{
    public function salonManifest(CurrentSalon $currentSalon): JsonResponse
    {
        $salon = $currentSalon->get();
        abort_if($salon === null, 404);

        $scope = rtrim((string) parse_url(route('home', ['salon_slug' => $salon->slug]), PHP_URL_PATH), '/').'/';
        $colors = self::colors($salon);
        $title = SalonTitle::for($salon->name);
        $icon = fn (string $variant) => route('salon.pwa.icon', ['salon_slug' => $salon->slug, 'variant' => $variant], false);

        return $this->manifest([
            'id' => $scope,
            'name' => $title,
            'short_name' => mb_substr($salon->name, 0, 12),
            'description' => 'رزرو آنلاین نوبت در '.$title,
            'start_url' => $scope.'?source=pwa',
            'scope' => $scope,
            'background_color' => $colors['background'],
            'theme_color' => $colors['background'],
            'icons' => [
                ['src' => $icon('192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon('512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon('maskable-512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ]);
    }

    public function salonIcon(CurrentSalon $currentSalon, SalonPwaIcon $icons, string $variant): BinaryFileResponse
    {
        $salon = $currentSalon->get();
        abort_if($salon === null || ! isset(SalonPwaIcon::VARIANTS[$variant]), 404);

        return response()->file($icons->path($salon, $variant, self::colors($salon)['icon_background']), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** پنل متخصص — عمومی (مرورگر manifest را بدون کوکی می‌گیرد)، پس عمومی و یکسان برای همه */
    public function specialistManifest(): JsonResponse
    {
        return $this->manifest([
            'id' => '/specialist/',
            'name' => config('brand.name').' — پنل متخصص',
            'short_name' => 'پنل متخصص',
            'description' => 'نوبت‌های امروز، تقویم و کیف پول متخصص',
            'start_url' => route('specialist.my-dashboard', [], false).'?source=pwa',
            'scope' => '/',
            'background_color' => '#1A1410',
            'theme_color' => '#1A1410',
            'icons' => [
                ['src' => '/brand/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/brand/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/brand/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ]);
    }

    public function serviceWorker(): Response
    {
        $build = public_path('build/manifest.json');
        $version = substr(md5((is_file($build) ? (string) md5_file($build) : 'dev').'|'.(string) @filemtime(resource_path('pwa/sw.js'))), 0, 12);

        return response(str_replace('__VERSION__', $version, (string) file_get_contents(resource_path('pwa/sw.js'))), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function offline(): Response
    {
        return response()->view('pwa.offline')->header('Cache-Control', 'no-cache');
    }

    /**
     * رنگ زمینه‌ی تیره‌ی پالت (اسپلش و نوار بالا) و زمینه‌ی روشن (پشت لوگو در آیکون)
     *
     * @return array{background: string, icon_background: string}
     */
    public static function colors(Salon $salon): array
    {
        $tokens = ThemeCatalog::sitePaletteTokens(SalonAppearance::for($salon)->palette);

        return [
            'background' => ThemeCatalog::toHex($tokens['dark'])['bg'] ?? '#1A1410',
            'icon_background' => ThemeCatalog::toHex($tokens['light'])['bg'] ?? '#F8F3E9',
        ];
    }

    /** @param  array<string, mixed>  $fields */
    private function manifest(array $fields): JsonResponse
    {
        return response()->json(['lang' => 'fa', 'dir' => 'rtl', 'display' => 'standalone', 'orientation' => 'portrait'] + $fields, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
