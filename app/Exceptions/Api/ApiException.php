<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * خطای قابل‌انتظار /api/v1 با کد ماشینی ثابت. ApiExceptionRenderer آن را به قالب یکسان تبدیل می‌کند.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $meta = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self('unauthenticated', 'ابتدا وارد حساب خود شوید.', 401);
    }

    public static function wrongApp(): self
    {
        return new self('wrong_app', 'این حساب برای این اپلیکیشن نیست.', 403);
    }

    public static function salonInactive(): self
    {
        return new self('salon_inactive', 'اشتراک این سالن پایان یافته یا غیرفعال شده است.', 403);
    }
}
