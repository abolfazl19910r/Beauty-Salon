<?php

namespace App\Services\Links;

use App\Models\ShortLink;

/**
 * لینک کوتاه برای پیامک (۲۰۲۶-۰۹-۳۰). خروجی بدون https:// است (گوشی‌ها لینک را خودشان تشخیص می‌دهند و هر نویسه در پیامک
 * هزینه دارد): «دامنه/b/کد». دامنه: SMS_LINK_HOST، وگرنه CENTRAL_DOMAIN، وگرنه میزبان APP_URL.
 */
class ShortLinkService
{
    public const CODE_LENGTH = 6;

    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyz23456789';

    public function shorten(string $targetUrl, ?\DateTimeInterface $expiresAt = null): string
    {
        // فقط حروف کوچک و رقم (بدون o/0/l/1 مبهم): collation دیتابیس به بزرگ/کوچکی حساس نیست و کاربر هم ممکن است دستی تایپ کند
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (ShortLink::where('code', $code)->exists());

        ShortLink::create(['code' => $code, 'target_url' => $targetUrl, 'expires_at' => $expiresAt]);

        return self::host().'/b/'.$code;
    }

    public static function host(): string
    {
        $host = config('services.sms_links.host')
            ?: config('app.central_domain')
            ?: (parse_url((string) config('app.url'), PHP_URL_HOST).(($port = parse_url((string) config('app.url'), PHP_URL_PORT)) ? ':'.$port : ''));

        return rtrim(preg_replace('#^https?://#', '', (string) $host), '/');
    }
}
