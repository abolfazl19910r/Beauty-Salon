<?php

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(2),
            'description' => fake()->optional()->sentence(),
            'image_path' => 'gallery/'.fake()->word().'.jpg',
            'order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
