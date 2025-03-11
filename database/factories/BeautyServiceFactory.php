<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BeautyService;
use App\Models\Category;
use Illuminate\Support\Str;

class BeautyServiceFactory extends Factory
{
    protected $model = BeautyService::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(50000, 1000000),
            'duration' => fake()->randomElement([30, 45, 60, 90, 120]),
            'category_id' => Category::factory()
        ];
    }
}
