<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        $userId = User::factory();

        return [
            'type' => 'App\Notifications\GeneralNotification',
            'data' => [
                'message' => fake()->sentence(),
                'icon' => 'info',
                'action_url' => fake()->url(),
            ],
            'read_at' => null,
            'user_id' => $userId,
            'notifiable_type' => User::class,
            'notifiable_id' => function (array $attributes) {
                return $attributes['user_id'];
            },
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
