<?php

namespace App\Exceptions;

/**
 * گذار وضعیت نوبت از وضعیت فعلی مجاز نیست (مثلاً پذیرش نوبت لغوشده). در API کد `invalid_booking_state` (409).
 */
class InvalidBookingStateException extends DomainException
{
    protected int $httpStatus = 409;

    public static function because(string $userMessage): self
    {
        $e = new self($userMessage);
        $e->userMessage = $userMessage;

        return $e;
    }
}
