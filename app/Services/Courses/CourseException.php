<?php

namespace App\Services\Courses;

use App\Exceptions\DomainException;

/**
 * خطای قابل نمایش به کاربر در کار با دوره (پیام فارسی). وب ← back با error؛ API ← ApiExceptionRenderer: ‏422 با code «course».
 */
class CourseException extends DomainException
{
    protected int $httpStatus = 422;
}
