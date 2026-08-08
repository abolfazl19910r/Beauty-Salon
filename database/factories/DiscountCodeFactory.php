<?php

namespace Database\Factories;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['fixed', 'percentage']);

        return [
            'code' => strtoupper(Str::random(8)),
            'type' => $type,
            'amount' => $type === 'percentage'
                ? fake()->numberBetween(5, 50)
                : fake()->numberBetween(10000, 100000),
            'max_uses' => fake()->numberBetween(10, 100),
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'user_id' => null,
        ];
    }

    public function percentage(?int $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'amount' => $amount ?? fake()->numberBetween(10, 50),
        ]);
    }

    public function fixed(?int $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'amount' => $amount ?? fake()->numberBetween(20000, 150000),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
            'is_active' => false,
        ]);
    }

    public function personal(User|callable|null $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user ?? User::factory(),
            'max_uses' => 1,
        ]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_count' => $attributes['max_uses'] ?? 10,
        ])->afterCreating(function (DiscountCode $code) {
            $code->update(['used_count' => $code->max_uses]);
        });
    }
}
