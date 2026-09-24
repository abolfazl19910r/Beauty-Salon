<?php

namespace App\Payments;

final class GatewayStartResult
{
    /**
     * @param  bool  $retryable  true فقط وقتی خود درگاه در دسترس نبود (قطعی شبکه/۵xx) — GatewayManager
     *                           فقط در همین حالت سراغ درگاه بعدی سالن می‌ره (failover). خطاهای منطقی
     *                           (مرچنت نامعتبر، مبلغ غیرمجاز) retryable نیستن.
     * @param  array<string, string>  $formFields  برای درگاه‌های بانکی که مشتری رو با فرم POST می‌فرستن.
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $token = null,
        public readonly string $method = 'GET',
        public readonly array $formFields = [],
        public readonly ?string $message = null,
        public readonly bool $retryable = false,
        public readonly array $raw = [],
    ) {}

    public static function redirect(string $url, string $token, array $raw = []): self
    {
        return new self(true, $url, $token, 'GET', [], null, false, $raw);
    }

    public static function failed(string $message, bool $retryable = false, array $raw = []): self
    {
        return new self(false, null, null, 'GET', [], $message, $retryable, $raw);
    }
}
