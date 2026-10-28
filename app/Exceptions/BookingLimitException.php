<?php

namespace App\Exceptions;

/**
 * سقف نوبت آنلاین مشتری پر است (بسته‌ی 5ه، تصمیم‌های ۱۲ تا ۱۶). API: error.code = booking_limit (۴۲۲).
 */
class BookingLimitException extends DomainException
{
    protected int $httpStatus = 422;

    public static function daily(int $limit): self
    {
        $count = $limit === 1 ? 'یک' : to_persian_num((string) $limit);

        return self::because("Daily online booking limit {$limit} reached.",
            "برای هر روز حداکثر {$count} نوبت آنلاین ممکن است؛ برای خدمت دوم با سالن تماس بگیرید.");
    }

    public static function active(int $limit): self
    {
        $count = $limit === 1 ? 'یک' : to_persian_num((string) $limit);

        return self::because("Active future booking limit {$limit} reached.",
            "حداکثر {$count} نوبت آینده‌ی فعال می‌توانید داشته باشید؛ برای نوبت بیشتر با سالن تماس بگیرید.");
    }

    private static function because(string $technical, string $userMessage): self
    {
        $e = new self($technical);
        $e->userMessage = $userMessage;

        return $e;
    }
}
