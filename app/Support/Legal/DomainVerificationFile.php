<?php

namespace App\Support\Legal;

use App\Support\PlatformSettings;

/**
 * فایل تأیید مالکیت دامنه (اینماد، ۲۰۲۶-۱۱-۱۱): اینماد برای تأیید مالکیت، جز ایمیل به info@دامنه، یک فایل txt می‌دهد که باید در ریشه‌ی
 * سایت باز شود (https://arisheh.ir/<نام>.txt). روی سرور image تک‌کانتینری public/ جزو image است و سرویس ایمیل نصب نمی‌کنیم؛ پس ابرمدیر
 * فایل را در «تنظیمات پلتفرم» بارگذاری می‌کند و Laravel عیناً همان بایت‌ها را برمی‌گرداند (route ‏domain-verification). فقط یک فایل در هر
 * زمان؛ برداشتن = ۴۰۴. همین برای هر سرویس دیگری که تأیید با فایل txt ریشه می‌خواهد هم کار می‌کند.
 */
final class DomainVerificationFile
{
    public const NAME_KEY = 'domain_verification_filename';

    public const CONTENT_KEY = 'domain_verification_content';

    /** نام فایل در ریشه: حرف/رقم اول، بعد حرف، رقم، نقطه، _ و -؛ پسوند .txt (مسیر، ../ و پسوند دیگر نه) */
    public const NAME_PATTERN = '[A-Za-z0-9][A-Za-z0-9._-]{0,95}\.txt';

    public const MAX_BYTES = 4096;

    /** نام‌هایی که مال خود سایت یا استانداردهای وب‌اند */
    private const RESERVED = ['robots.txt', 'ads.txt', 'app-ads.txt', 'security.txt', 'humans.txt', 'llms.txt'];

    public static function problem(?string $name, ?string $content): ?string
    {
        $name = (string) $name;
        if (! preg_match('/^'.self::NAME_PATTERN.'$/', $name) || str_contains($name, '..')) {
            return 'نام فایل باید همان نام فایل اینماد باشد: فقط حروف لاتین، رقم، نقطه، خط تیره و _، با پسوند .txt (بی پوشه).';
        }
        if (in_array(strtolower($name), self::RESERVED, true)) {
            return 'این نام مال خود سایت است و نمی‌شود برای فایل تأیید به کار برد.';
        }
        // فایل اینماد خالی است (۰ بایت؛ ۲۰۲۶-۱۱-۱۱): فقط نامش سنجیده می‌شود — خالی مجاز است
        if (strlen((string) $content) > self::MAX_BYTES) {
            return 'فایل تأیید بزرگ‌تر از ۴ کیلوبایت است؛ فایل درست اینماد را انتخاب کنید.';
        }
        if (! mb_check_encoding((string) $content, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', (string) $content)) {
            return 'فایل متنی ساده نیست (نویسه‌ی دودویی دارد).';
        }

        return null;
    }

    /** @return array{name: string, content: string}|null */
    public static function current(): ?array
    {
        $name = PlatformSettings::raw(self::NAME_KEY);
        // نام تعیین‌کننده است؛ محتوای خالی (فایل ۰ بایتی اینماد) هم یک فایل ذخیره‌شده است
        return $name !== null ? ['name' => $name, 'content' => (string) (PlatformSettings::stored()[self::CONTENT_KEY] ?? '')] : null;
    }

    public static function contentFor(string $name): ?string
    {
        $current = self::current();

        return $current !== null && hash_equals($current['name'], $name) ? $current['content'] : null;
    }

    public static function save(string $name, string $content): void
    {
        // محتوا عیناً (بی trim) — PlatformSettings::put فاصله‌ی دو سر را می‌برد، پس مستقیم در جدول
        \App\Models\PlatformSetting::query()->updateOrCreate(['key' => self::CONTENT_KEY], ['value' => $content]);
        PlatformSettings::put(self::NAME_KEY, $name);
    }

    public static function clear(): void
    {
        PlatformSettings::put(self::NAME_KEY, null);
        PlatformSettings::put(self::CONTENT_KEY, null);
    }
}
