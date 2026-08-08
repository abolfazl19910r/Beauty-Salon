<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'is_active' => true,
            'type' => fake()->randomElement(['general', 'maintenance', 'promotion']),
            'priority' => fake()->numberBetween(1, 10),
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'maintenance',
            'priority' => 10,
        ]);
    }

    public function promotion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promotion',
            'priority' => 5,
        ]);
    }
}
