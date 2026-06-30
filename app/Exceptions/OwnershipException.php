<?php

namespace App\Exceptions;

class OwnershipException extends DomainException
{
    protected int $httpStatus = 403;

    protected ?string $userMessage = 'شما به این بخش دسترسی ندارید.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param string $resourceType نوع منبع (مثل 'booking', 'wallet')
     * @param int|string|null $resourceId آیدی منبع
     * @param int|string|null $ownerId آیدی مالک واقعی
     * @param int|string|null $actorId آیدی کاربری که سعی کرده دسترسی پیدا کنه
     */
    public static function for(
        string $resourceType,
               $resourceId = null,
               $ownerId = null,
               $actorId = null
    ): self {
        $instance = new self(
            "Ownership violation on {$resourceType}#{$resourceId}: owner={$ownerId}, actor={$actorId}"
        );
        $instance->contextData = [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'owner_id' => $ownerId,
            'actor_id' => $actorId,
        ];

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
