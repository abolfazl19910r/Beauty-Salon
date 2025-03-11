<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BlogPost;
use Illuminate\Support\Str;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence();
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->paragraphs(5, true),
            'excerpt' => fake()->paragraph(),
            'image' => null,
            'category_id' => BlogCategory::factory(),
            'author_id' => User::factory(),
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => now()->addDays(5),
        ]);
    }
}
