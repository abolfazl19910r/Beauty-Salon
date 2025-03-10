<?php

namespace Database\Factories;

use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeautyServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(50000, 1000000),
            'duration' => fake()->randomElement([30, 45, 60, 90, 120]),
            'category_id' => CategoryService::factory()
        ];
    }
}
