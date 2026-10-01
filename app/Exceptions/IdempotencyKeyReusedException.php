<?php

namespace App\Exceptions;

use RuntimeException;

/** همان idempotency_key با داده‌ی دیگری فرستاده شد (فرم کهنه با مقدار عوض‌شده) — درخواست نه تکرار است نه جدید. */
class IdempotencyKeyReusedException extends RuntimeException
{
    public const USER_MESSAGE = 'این فرم قبلاً با اطلاعات دیگری ثبت شده است. لطفاً صفحه را دوباره باز کنید.';

    public function __construct()
    {
        parent::__construct(self::USER_MESSAGE);
    }
}
