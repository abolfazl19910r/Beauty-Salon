<?php

namespace App\Support\Themes;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فهرست قالب‌ها (بسته‌ی «و»): خواندن config/themes.php، ترتیب باز شدن چیدمان‌ها و پالت‌ها (ابرمدیر) و توکن‌ها.
 *
 * کلید قالب سایت: «mahru» (پیش‌فرض) یا «چیدمان:پالت» (vitrin:rose). کلید قالب پنل مدیر: side (پیش‌فرض)، rail، top، dense.
 * قالب‌های باز یک پلن = چند چیدمانِ اول × چند پالتِ اول به ترتیبی که ابرمدیر چیده (Plan::siteThemeGrid).
 */
final class ThemeCatalog
{
    public const DEFAULT_SITE = 'mahru';

    public const DEFAULT_ADMIN = 'side';

    private const ORDER_CACHE = 'themes:order';

    private const LAYOUT_ORDER_KEY = 'theme_layout_order';

    private const PALETTE_ORDER_KEY = 'theme_palette_order';

    /** @return array<string, array{name: string, description: string, font: string}> به ترتیب ابرمدیر */
    public static function layouts(): array
    {
        $all = (array) config('themes.layouts');

        return array_merge(array_flip(self::layoutOrder()), array_intersect_key($all, array_flip(self::layoutOrder())));
    }

    /** @return array<string, array<string, mixed>> به ترتیب ابرمدیر */
    public static function palettes(): array
    {
        $all = (array) config('themes.palettes');

        return array_merge(array_flip(self::paletteOrder()), array_intersect_key($all, array_flip(self::paletteOrder())));
    }

    /** @return array<string, array<string, mixed>> side اول (پیش‌فرض)، بعد بقیه به ترتیب config */
    public static function adminThemes(): array
    {
        return (array) config('themes.admin');
    }

    public static function layoutCount(): int
    {
        return count((array) config('themes.layouts'));
    }

    public static function paletteCount(): int
    {
        return count((array) config('themes.palettes'));
    }

    /** چند قالب سایت جدا از پیش‌فرض (۱۶) */
    public static function siteTotal(): int
    {
        return self::layoutCount() * self::paletteCount();
    }

    /** چند قالب پنل مدیر جدا از پیش‌فرض (۳) */
    public static function adminTotal(): int
    {
        return max(0, count(self::adminThemes()) - 1);
    }

    /** @return list<string> */
    public static function layoutOrder(): array
    {
        return self::order()['layouts'];
    }

    /** @return list<string> */
    public static function paletteOrder(): array
    {
        return self::order()['palettes'];
    }

    /** @return list<string> همه‌ی کلیدهای سایت (بی پیش‌فرض)، چیدمان به چیدمان */
    public static function siteKeys(): array
    {
        $keys = [];
        foreach (self::layoutOrder() as $layout) {
            foreach (self::paletteOrder() as $palette) {
                $keys[] = $layout.':'.$palette;
            }
        }

        return $keys;
    }

    /** @return list<string> */
    public static function adminKeys(): array
    {
        return array_keys(self::adminThemes());
    }

    public static function isSiteKey(?string $key): bool
    {
        return $key === self::DEFAULT_SITE || in_array($key, self::siteKeys(), true);
    }

    public static function isAdminKey(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::adminThemes());
    }

    /** @return array{0: string, 1: ?string} [چیدمان، پالت]؛ پیش‌فرض = ['mahru', null] */
    public static function splitSite(string $key): array
    {
        if ($key === self::DEFAULT_SITE || ! str_contains($key, ':')) {
            return [self::DEFAULT_SITE, null];
        }
        [$layout, $palette] = explode(':', $key, 2);

        return [$layout, $palette];
    }

    /**
     * قالب‌های سایتِ باز با این تعداد چیدمان و پالت (بی پیش‌فرض).
     *
     * @return list<string>
     */
    public static function allowedSiteKeys(int $layouts, int $palettes): array
    {
        $layouts = max(0, min($layouts, self::layoutCount()));
        $palettes = max(0, min($palettes, self::paletteCount()));
        $keys = [];
        foreach (array_slice(self::layoutOrder(), 0, $layouts) as $layout) {
            foreach (array_slice(self::paletteOrder(), 0, $palettes) as $palette) {
                $keys[] = $layout.':'.$palette;
            }
        }

        return $keys;
    }

    /** @return list<string> پیش‌فرض + چند قالب اول دیگر */
    public static function allowedAdminKeys(int $count): array
    {
        $keys = self::adminKeys();

        return array_slice($keys, 0, 1 + max(0, min($count, self::adminTotal())));
    }

    public static function siteName(string $key): string
    {
        if ($key === self::DEFAULT_SITE) {
            return (string) config('themes.mahru.name');
        }
        [$layout, $palette] = self::splitSite($key);

        return config("themes.layouts.$layout.name").' · '.config("themes.palettes.$palette.name");
    }

    public static function adminName(string $key): string
    {
        return (string) config("themes.admin.$key.name", config('themes.admin.side.name'));
    }

    /**
     * توکن‌های یک پالت سایت (یا «mahru») برای تیره و روشن.
     *
     * @return array{dark: array<string, string>, light: array<string, string>}
     */
    public static function sitePaletteTokens(?string $palette): array
    {
        $source = $palette === null ? config('themes.mahru') : config("themes.palettes.$palette");

        return ['dark' => (array) ($source['dark'] ?? []), 'light' => (array) ($source['light'] ?? [])];
    }

    /** @return array{dark: array<string, string>, light: array<string, string>}|null null = طلایی ماهرو */
    public static function adminPaletteTokens(string $admin): ?array
    {
        $palette = config("themes.admin.$admin.palette");

        return is_array($palette) ? ['dark' => (array) $palette['dark'], 'light' => (array) $palette['light']] : null;
    }

    /**
     * هگز برای اپ‌ها (React Native کانال RGB نمی‌فهمد).
     *
     * @param  array<string, string>  $tokens
     * @return array<string, string>
     */
    public static function toHex(array $tokens): array
    {
        $map = ['bg' => 'bg', 'bg2' => 'surface', 'bg3' => 'surfaceAlt', 'card' => 'card', 'tx' => 'text', 'mu' => 'muted', 'gold' => 'accent', 'gold2' => 'accentStrong'];
        $out = [];
        foreach ($map as $from => $to) {
            if (isset($tokens[$from])) {
                $out[$to] = self::rgbToHex($tokens[$from]);
            }
        }
        $out['onAccent'] = strtoupper((string) ($tokens['on'] ?? '#FFFFFF'));
        $out['line'] = (string) ($tokens['line'] ?? '');
        $out['lineStrong'] = (string) ($tokens['line2'] ?? '');

        return $out;
    }

    public static function rgbToHex(string $channels): string
    {
        $parts = array_map('intval', preg_split('/\s+/', trim($channels)) ?: []);

        return sprintf('#%02X%02X%02X', $parts[0] ?? 0, $parts[1] ?? 0, $parts[2] ?? 0);
    }

    /**
     * ترتیب تازه‌ی باز شدن (ابرمدیر). هر فهرست باید دقیقاً همه‌ی کلیدهای config را داشته باشد.
     *
     * @param  list<string>  $layouts
     * @param  list<string>  $palettes
     */
    public static function saveOrder(array $layouts, array $palettes): void
    {
        $validLayouts = array_keys((array) config('themes.layouts'));
        $validPalettes = array_keys((array) config('themes.palettes'));
        if (! self::samePermutation($layouts, $validLayouts) || ! self::samePermutation($palettes, $validPalettes)) {
            throw new \InvalidArgumentException('Theme order must list every layout and palette exactly once.');
        }

        PlatformSetting::query()->updateOrCreate(['key' => self::LAYOUT_ORDER_KEY], ['value' => json_encode(array_values($layouts))]);
        PlatformSetting::query()->updateOrCreate(['key' => self::PALETTE_ORDER_KEY], ['value' => json_encode(array_values($palettes))]);
        Cache::forget(self::ORDER_CACHE);
    }

    /** @return array{layouts: list<string>, palettes: list<string>} */
    private static function order(): array
    {
        $validLayouts = array_keys((array) config('themes.layouts'));
        $validPalettes = array_keys((array) config('themes.palettes'));

        $stored = Cache::rememberForever(self::ORDER_CACHE, function () {
            if (! Schema::hasTable('platform_settings')) {
                return [];
            }

            return PlatformSetting::query()
                ->whereIn('key', [self::LAYOUT_ORDER_KEY, self::PALETTE_ORDER_KEY])
                ->pluck('value', 'key')
                ->all();
        });

        $layouts = json_decode((string) ($stored[self::LAYOUT_ORDER_KEY] ?? ''), true);
        $palettes = json_decode((string) ($stored[self::PALETTE_ORDER_KEY] ?? ''), true);

        return [
            // ترتیب ذخیره‌شده‌ای که با config نمی‌خواند (قالبی اضافه/حذف شده) نادیده گرفته می‌شود
            'layouts' => is_array($layouts) && self::samePermutation($layouts, $validLayouts) ? array_values($layouts) : $validLayouts,
            'palettes' => is_array($palettes) && self::samePermutation($palettes, $validPalettes) ? array_values($palettes) : $validPalettes,
        ];
    }

    /** @param list<mixed> $given @param list<string> $valid */
    private static function samePermutation(array $given, array $valid): bool
    {
        $given = array_values($given);
        if (count($given) !== count($valid) || count(array_unique($given)) !== count($given)) {
            return false;
        }
        sort($given);
        sort($valid);

        return $given === $valid;
    }
}
