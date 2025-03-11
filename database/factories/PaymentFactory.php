<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $booking = Booking::factory()->create();

        return [
            'booking_id' => $booking->id,
            'amount' => $booking->prepayment_amount,
            'reference_id' => Str::random(12),
            'status' => 'pending',
            'gateway_reference' => null,
            'gateway_response' => null,
            'payment_details' => null,
            'paid_at' => null,
            'expired_at' => now()->addHours(1),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
            'gateway_reference' => 'TRX' . Str::random(8),
            'gateway_response' => json_encode(['status' => 'success']),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'gateway_response' => json_encode(['status' => 'failed']),
        ]);
    }
}
