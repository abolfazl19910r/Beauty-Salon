<?php

namespace App\Exceptions;

/** پیش‌نیاز «رزرو با پیش‌پرداخت» فراهم نیست (بسته‌ی 5ه) */
class BookingModeException extends DomainException
{
    protected int $httpStatus = 422;

    public static function because(string $userMessage): self
    {
        $e = new self($userMessage);
        $e->userMessage = $userMessage;

        return $e;
    }
}
