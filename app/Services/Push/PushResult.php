<?php

namespace App\Services\Push;

/**
 * نتیجه‌ی یک ارسال به FCM/ntfy.
 *
 * - ok: پذیرفته شد (نه یعنی رسید؛ رسید را خود اپ می‌فرستد).
 * - invalidToken: توکن دستگاه دیگر معتبر نیست (اپ حذف شد، داده پاک شد) — دستگاه غیرفعال می‌شود.
 * - retryable: خطای گذرا (شبکه، 429، 5xx) — job با فاصله دوباره امتحان می‌کند.
 * - outage: سرور اصلاً به سرویس نرسید (DNS/اتصال یا توکن دسترسی گوگل) — پیام‌های فوری/عادی همان لحظه پیامک می‌شوند.
 */
final class PushResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $status = null,
        public readonly ?string $error = null,
        public readonly bool $invalidToken = false,
        public readonly bool $retryable = false,
        public readonly bool $outage = false,
    ) {}

    public static function accepted(int $status = 200): self
    {
        return new self(true, $status);
    }

    public static function outage(string $error): self
    {
        return new self(false, null, $error, retryable: true, outage: true);
    }
}
