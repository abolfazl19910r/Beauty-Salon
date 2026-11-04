<?php

namespace App\Support\Sms;

/**
 * کد تأیید در لاگ فقط وقتی پیامک واقعاً نمی‌رود (بررسی امنیتی ۲۰۲۶-۱۱-۰۴، M2).
 *
 * قبلاً چند سرویس کد ورود/ثبت‌نام را همیشه با Log::info می‌نوشتند؛ روی سرور (LOG_LEVEL=info) کدها در storage/logs، `docker logs` و
 * پشتیبان‌ها می‌ماندند. حالا کد فقط در حالت «فقط لاگ» (SMS_LOG_ONLY=true، یا local/testing بی KAVENEGAR_SEND_IN_LOCAL) — همان
 * حالتی که تنها راه دیدن کد لاگ است — نوشته می‌شود.
 */
final class OtpLog
{
    /** پیامک به کاوه‌نگار نمی‌رود و فقط لاگ می‌شود؟ (منبع واحد؛ SMSService هم همین را می‌پرسد) */
    public static function logOnlyMode(): bool
    {
        if (config('services.kavenegar.log_only', false)) {
            return true;
        }

        return app()->environment(['local', 'testing']) && ! config('services.kavenegar.send_in_local', false);
    }

    /** @return array{code?: string} برای context لاگ */
    public static function code(string $code): array
    {
        return self::logOnlyMode() ? ['code' => $code] : [];
    }

    /** @param  array<int, mixed>  $tokens */
    public static function tokens(array $tokens): array
    {
        return self::logOnlyMode() ? $tokens : array_map(fn () => '***', $tokens);
    }
}
