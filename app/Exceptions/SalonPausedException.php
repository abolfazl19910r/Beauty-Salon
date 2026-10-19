<?php

namespace App\Exceptions;

use App\Models\Salon;

/**
 * بسته‌ی «ح»، ۸: رزرو تازه در سالن موقتاً متوقف است. ۴۲۳ (Locked)؛ در API نوعش SalonPausedException است و اپ همان پیام را نشان می‌دهد.
 */
class SalonPausedException extends DomainException
{
    protected int $httpStatus = 423;

    public static function for(Salon $salon): self
    {
        $e = new self($salon->bookingPauseMessage());
        $e->userMessage = $salon->bookingPauseMessage();

        return $e;
    }
}
