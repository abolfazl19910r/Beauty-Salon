<?php

namespace Database\Factories;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalonFactory extends Factory
{
    protected $model = Salon::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'max_specialists_count' => fake()->numberBetween(5, 50),
            'module_permissions' => null,
            'subscription_type' => fake()->randomElement(['1m', '3m', '6m', '12m']),
            'subscription_started_at' => now(),
            'subscription_ends_at' => now()->addMonths(12),
            'is_suspended' => false,
            'created_by' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['is_suspended' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_started_at' => now()->subMonths(13),
            'subscription_ends_at' => now()->subDay(),
        ]);
    }
}
