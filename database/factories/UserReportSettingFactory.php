<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserReportSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserReportSettingFactory extends Factory
{
    protected $model = UserReportSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'settings' => [
                'dashboard_layout' => fake()->randomElement(['grid', 'list']),
                'default_report_type' => fake()->randomElement(['daily', 'weekly', 'monthly']),
                'default_date_range' => fake()->randomElement(['7', '30', '90']),
                'chart_colors' => [
                    'primary' => fake()->hexColor(),
                    'secondary' => fake()->hexColor(),
                    'accent' => fake()->hexColor(),
                ],
                'notifications' => [
                    'email' => fake()->boolean(),
                    'browser' => fake()->boolean(),
                ],
                'export_settings' => [
                    'include_charts' => fake()->boolean(),
                    'preferred_format' => fake()->randomElement(['excel', 'pdf', 'csv']),
                ],
            ],
        ];
    }
}
