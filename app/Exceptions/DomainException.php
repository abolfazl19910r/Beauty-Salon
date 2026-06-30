<?php

namespace App\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    public function httpStatusCode(): int
    {
        return 422;
    }

    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
