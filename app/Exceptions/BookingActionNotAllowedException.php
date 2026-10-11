<?php

namespace App\Exceptions;

/**
 * کاری که مشتری روی نوبت خواسته در وضعیت فعلی نوبت مجاز نیست (امتیاز دوباره، امتیاز به نوبت انجام‌نشده، ...).
 * در API: 409 booking_action_not_allowed با پیام فارسی.
 */
class BookingActionNotAllowedException extends DomainException
{
    protected int $httpStatus = 409;

    public static function because(string $userMessage): self
    {
        $instance = new self($userMessage);
        $instance->userMessage = $userMessage;

        return $instance;
    }
}
