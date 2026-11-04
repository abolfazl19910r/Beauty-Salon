<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->realText(50);

        return [
            'title' => $title,
            // ⭐ 5ح: realText فارسی بعد از Str::slug کوتاه و تکراری می‌شد ← تکرار (salon_id, slug) و شکست migrate:fresh --seed
            'slug' => ltrim(Str::slug($title).'-'.Str::lower(Str::random(6)), '-'),
            'content' => fake()->realText(1000),
            'excerpt' => fake()->realText(150),
            'image' => null,
            'category_id' => BlogCategory::inRandomOrder()->first()?->id ?? BlogCategory::factory(),
            'author_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'views' => fake()->numberBetween(0, 5000),
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
            'published_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
