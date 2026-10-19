<?php

namespace App\Services\Salon;

use App\Exceptions\DomainException;

/** مدیر سالن نمی‌تواند توقف ابرمدیر را عوض کند */
class SalonPauseException extends DomainException
{
    protected int $httpStatus = 403;
}
