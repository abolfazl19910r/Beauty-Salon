<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

abstract class DomainException extends RuntimeException
{
    protected int $httpStatus = 400;

    protected ?string $userMessage = null;

    public function context(): array
    {
        return [];
    }

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage ?? $this->getMessage();
    }
}
