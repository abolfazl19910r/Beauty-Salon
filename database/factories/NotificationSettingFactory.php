<?php

namespace Database\Factories;

use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'salon_id' => app(CurrentSalon::class)->id(),
            'event_key' => 'test.'.fake()->unique()->word(),
            'label' => fake()->sentence(3),
            'sms_enabled' => true,
            'database_enabled' => true,
            'telegram_enabled' => false,
        ];
    }
}
