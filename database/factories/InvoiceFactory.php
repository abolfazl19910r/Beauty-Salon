<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'salon_id' => Salon::factory(),
            'subscription_type' => fake()->randomElement(['1m', '3m', '6m', '12m']),
            'amount' => fake()->numberBetween(400000, 4500000),
            'status' => 'pending',
            'payment_method' => 'online',
            'authority' => null,
            'ref_id' => null,
            'period_start' => null,
            'period_end' => null,
            'paid_at' => null,
            'created_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'ref_id' => 'REF-'.fake()->unique()->numberBetween(100000, 999999),
            'paid_at' => now(),
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'manual',
        ]);
    }
}
