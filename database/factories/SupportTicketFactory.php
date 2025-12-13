<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(5, true),
            'description' => fake()->paragraphs(2, true),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'open',
            'category' => fake()->randomElement(['booking', 'payment', 'service', 'technical', 'other']),
            'assigned_to' => null,
            'metadata' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'assigned_to' => User::factory(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'resolved_at' => now()->subDays(fake()->numberBetween(5, 15)),
            'closed_at' => now()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }
}
