<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);

        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'specialist_id' => Specialist::factory(),
            'service_id' => \App\Models\BeautyService::factory(),
            'overall_rating' => $rating,
            'quality_rating' => $rating,
            'behavior_rating' => $rating,
            'cleanliness_rating' => $rating,
            'speed_rating' => $rating,
            'comment' => $this->faker->sentence(),
            'is_approved' => true,
            'is_featured' => false,
        ];
    }

    public function negative(): static
    {
        return $this->state(fn () => [
            'overall_rating' => 1,
            'quality_rating' => 1,
            'behavior_rating' => 1,
            'cleanliness_rating' => 1,
            'speed_rating' => 1,
        ]);
    }

    public function unapproved(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
