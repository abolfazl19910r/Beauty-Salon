<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_key' => 'test.'.fake()->unique()->word(),
            'label' => fake()->sentence(3),
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ];
    }
}
