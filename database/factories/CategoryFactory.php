<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 3), true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(2),
            'parent_id' => null,
            'is_active' => true,
            'icon' => fake()->randomElement(['fa-cut', 'fa-nail', 'fa-face']),
            'image' => null,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    public function withParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory()->state(['parent_id' => null]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
