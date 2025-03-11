<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Specialist;

class SpecialistFactory extends Factory
{
    protected $model = Specialist::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'email' => fake()->unique()->safeEmail()
        ];
    }
}
