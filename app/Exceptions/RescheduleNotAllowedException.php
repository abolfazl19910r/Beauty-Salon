<?php

namespace App\Exceptions;

use App\Models\Booking;

class RescheduleNotAllowedException extends DomainException
{
    protected int $httpStatus = 422; // Unprocessable Entity

    protected ?string $userMessage = 'این نوبت در حال حاضر قابل تغییر زمان نیست.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    public static function forBooking(Booking $booking, string $reason): self
    {
        $instance = new self($reason);
        $instance->contextData = [
            'booking_id' => $booking->id,
            'current_status' => $booking->status,
            'booking_time' => $booking->booking_time?->toDateTimeString(),
        ];

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
