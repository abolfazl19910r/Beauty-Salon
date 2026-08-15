<?php

namespace Database\Factories;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityLogFactory extends Factory
{
    protected $model = SecurityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->randomElement(['login_attempt', 'session_terminated', 'profile_change']),
            'level' => fake()->randomElement(['info', 'warning']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'context' => [],
            'created_at' => now(),
        ];
    }
}
