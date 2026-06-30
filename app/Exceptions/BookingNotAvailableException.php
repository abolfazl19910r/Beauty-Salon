<?php

namespace App\Exceptions;

class BookingNotAvailableException extends DomainException
{
    public static function slotTaken(): self
    {
        return new self('این بازه‌ی زمانی قبلاً رزرو شده است. لطفاً زمان دیگری انتخاب کنید.');
    }

    public static function outsideWorkingHours(): self
    {
        return new self('این بازه‌ی زمانی خارج از ساعت کاری متخصص است.');
    }

    public static function specialistOnLeave(): self
    {
        return new self('متخصص در این تاریخ مرخصی است.');
    }

    public static function tooLateToReschedule(): self
    {
        return new self('امکان تغییر زمان این نوبت وجود ندارد (کمتر از ۲۴ ساعت تا زمان نوبت باقی مانده است).');
    }

    public function httpStatusCode(): int
    {
        return 409;
    }
}
