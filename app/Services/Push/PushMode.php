<?php

namespace App\Services\Push;

use App\Support\PlatformSettings;

/**
 * حالت پوش پلتفرم (تصمیم ۴ بسته‌ی ۴): یک کلید سراسری دست ابرمدیر.
 *
 * - off: هیچ پوشی؛ همه‌چیز مثل پیش از بسته‌ی ۴ (فقط پیامک).
 * - shadow: دوره‌ی آزمایشی — پیامک مثل قبل همیشه می‌رود و پوش هم؛ نرخ رسید سنجیده می‌شود. پیش‌فرض بعد از نصب.
 * - live: پوش هوشمند — پیامک فقط وقتی رسید در مهلت نیامد (یا پیام فوری به دستگاه ناسالم).
 */
final class PushMode
{
    public const OFF = 'off';

    public const SHADOW = 'shadow';

    public const LIVE = 'live';

    public const ALL = [self::OFF, self::SHADOW, self::LIVE];

    public const SETTING_KEY = 'push_mode';

    public static function current(): string
    {
        $mode = PlatformSettings::raw(self::SETTING_KEY) ?? (string) config('push.default_mode');

        return in_array($mode, self::ALL, true) ? $mode : self::SHADOW;
    }

    public static function set(string $mode): void
    {
        if (! in_array($mode, self::ALL, true)) {
            throw new \InvalidArgumentException("Unknown push mode [{$mode}]");
        }

        PlatformSettings::put(self::SETTING_KEY, $mode);
    }

    public static function label(string $mode): string
    {
        return match ($mode) {
            self::OFF => 'خاموش (فقط پیامک)',
            self::SHADOW => 'آزمایشی (پیامک + پوش، سنجش رسید)',
            self::LIVE => 'فعال (پوش؛ پیامک فقط بدون رسید)',
            default => $mode,
        };
    }
}
