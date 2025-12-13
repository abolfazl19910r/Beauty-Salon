<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $booking = Booking::factory()->create();

        $status = fake()->randomElement(['pending', 'completed', 'failed']);

        $paidAt = $status === 'completed' ? now()->subDays(rand(1, 10)) : null;

        return [
            'booking_id' => $booking->id,
            'amount' => $booking->prepayment_amount ?? fake()->numberBetween(10000, 500000),
            'reference_id' => Str::random(12),
            'card_data' => fake()->optional(0.5)->numberBetween(1000, 9999) . '****',
            'status' => $status,
            'gateway_reference' => $status === 'completed' ? 'TRX' . Str::random(8) : null,
            'gateway_response' => $status === 'failed' ? json_encode(['error' => 'Connection failed']) : null,
            'payment_details' => null,
            'paid_at' => $paidAt,
            'expired_at' => now()->addHours(1),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
            'gateway_reference' => 'TRX' . Str::upper(Str::random(8)),
            'gateway_response' => json_encode(['message' => 'Payment successful']),
        ])->afterCreating(function (Payment $payment) {
            $payment->booking()->update(['payment_status' => 'paid', 'paid_at' => $payment->paid_at]);
        });
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
            'gateway_response' => json_encode(['error' => 'Gateway declined']),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
            'expired_at' => now()->addMinutes(30),
        ]);
    }
}
