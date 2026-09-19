<?php

namespace App\Exceptions;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» (تصمیم تأییدشده): سالن باید همیشه حداقل یک
 * owner فعال داشته باشد — حذف/تنزل‌به‌staff/غیرفعال‌سازی آخرین owner مسدود می‌شود.
 */
class LastSalonOwnerException extends DomainException
{
    protected int $httpStatus = 422;

    public static function cannot(string $action): self
    {
        return new self(
            "این کاربر تنها مالک این سالن است؛ ابتدا یک مالک دیگر برای این سالن تعیین کنید تا او بتواند {$action}."
        );
    }
}
