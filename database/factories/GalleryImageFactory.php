<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GalleryImage;

class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(2),
            'description' => fake()->optional()->sentence(),
            'image_path' => 'gallery/' . fake()->word() . '.jpg',
            'order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
