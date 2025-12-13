<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BeautyServiceFactory extends Factory
{
    protected $model = BeautyService::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $subCategory = Category::whereNotNull('parent_id')->inRandomOrder()->first();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(50000, 1000000),
            'duration' => fake()->randomElement([30, 45, 60, 90, 120]),
            'image' => null,
            'category_id' => $subCategory ? $subCategory->id : null,
        ];
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
