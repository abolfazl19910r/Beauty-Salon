<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserNotification;
use Illuminate\Support\Str;

class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'type' => fake()->randomElement([
                'BookingConfirmed',
                'BookingCancelled',
                'PaymentReceived',
                'AppointmentReminder'
            ]),
            'user_id' => User::factory(),
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => function (array $attributes) {
                return $attributes['user_id'];
            },
            'data' => [
                'title' => fake()->sentence(),
                'message' => fake()->paragraph(),
                'action_url' => fake()->optional()->url(),
                'icon' => fake()->randomElement(['info', 'success', 'warning', 'error'])
            ],
            'read_at' => fake()->optional()->dateTime(),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function bookingConfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'BookingConfirmed',
            'data' => [
                'title' => 'رزرو تایید شد',
                'message' => 'نوبت شما با موفقیت تایید شد.',
                'icon' => 'success'
            ],
        ]);
    }

    public function paymentReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'PaymentReceived',
            'data' => [
                'title' => 'پرداخت دریافت شد',
                'message' => 'پرداخت شما با موفقیت انجام شد.',
                'icon' => 'success'
            ],
        ]);
    }
}
