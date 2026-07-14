<?php

namespace App\Exceptions;

class InsufficientLoyaltyPointsException extends DomainException
{
    protected int $httpStatus = 422;

    public function __construct(int $userId, int $balance, int $required)
    {
        parent::__construct(
            "User {$userId} has insufficient loyalty points: balance={$balance}, required={$required}"
        );

        $this->userMessage = 'موجودی امتیاز کاربر کافی نیست.';
    }

    public function context(): array
    {
        return ['domain' => 'loyalty_points'];
    }
}
