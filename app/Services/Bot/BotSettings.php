<?php

namespace App\Services\Bot;

use App\Support\PlatformSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * توکن، نام و آدرس API ربات‌های بله/تلگرام (۲۰۲۶-۱۰-۱۵): اول مقداری که ابرمدیر در «تنظیمات پلتفرم» گذاشته، بعد .env
 * (BALE_BOT_TOKEN و ...). توکن رمزنگاری‌شده با APP_KEY در platform_settings است و هرگز کامل نمایش داده نمی‌شود.
 */
final class BotSettings
{
    public const MESSENGERS = ['bale', 'telegram'];

    /**
     * کلید کل ربات (BOT_ENABLED، بسته‌ی ۸). خاموش = ربات از همه‌ی صفحه‌ها پنهان است، اتصال/webhook ۴۰۴ می‌دهند و پیامی نمی‌رود؛
     * جدول‌ها، اتصال‌های قبلی، توکن‌ها و تیک‌های ستون «ربات» دست نمی‌خورند تا با روشن کردن دوباره همه برگردد.
     */
    public static function featureEnabled(): bool
    {
        return (bool) config('services.bot.enabled');
    }

    public static function token(string $messenger): ?string
    {
        return self::panelToken($messenger) ?? (config("services.{$messenger}.bot_token") ?: null);
    }

    public static function username(string $messenger): ?string
    {
        return PlatformSettings::raw("bot_{$messenger}_username") ?? (config("services.{$messenger}.username") ?: null);
    }

    public static function apiBase(string $messenger): string
    {
        return rtrim((string) (PlatformSettings::raw("bot_{$messenger}_api_base") ?? config("services.{$messenger}.api_base")), '/');
    }

    /** panel | env | null */
    public static function tokenSource(string $messenger): ?string
    {
        if (self::panelToken($messenger) !== null) {
            return 'panel';
        }

        return config("services.{$messenger}.bot_token") ? 'env' : null;
    }

    /** چهار رقم آخر توکن، برای نمایش */
    public static function masked(string $messenger): ?string
    {
        $token = self::token($messenger);

        return $token ? '••••'.mb_substr($token, -4) : null;
    }

    public static function saveToken(string $messenger, ?string $token): void
    {
        PlatformSettings::put("bot_{$messenger}_token", $token ? Crypt::encryptString(trim($token)) : null);
    }

    public static function saveUsername(string $messenger, ?string $username): void
    {
        PlatformSettings::put("bot_{$messenger}_username", $username ? ltrim(trim($username), '@') : null);
    }

    public static function saveApiBase(string $messenger, ?string $url): void
    {
        PlatformSettings::put("bot_{$messenger}_api_base", $url ? rtrim(trim($url), '/') : null);
    }

    private static function panelToken(string $messenger): ?string
    {
        $stored = PlatformSettings::raw("bot_{$messenger}_token");
        if ($stored === null) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            // APP_KEY عوض شده: توکن پنل دیگر خوانا نیست — به .env برمی‌گردد تا ابرمدیر دوباره وارد کند
            return null;
        }
    }
}
