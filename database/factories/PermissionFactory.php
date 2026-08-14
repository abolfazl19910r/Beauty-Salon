<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = fake()->unique()->slug(2, '-');

        return [
            'name' => $name,
            'label' => fake()->words(3, true),
            'group' => fake()->randomElement(['booking', 'wallet', 'content', 'security', 'general']),
            'description' => fake()->sentence(),
        ];
    }
}
