<?php

namespace Database\Factories;

use App\Models\Role;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function system(): static
    {
        return $this->state(['salon_id' => null]);
    }

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'salon_id' => app(CurrentSalon::class)->id(),
            'name' => $name,
            'label' => fake()->sentence(2),
        ];
    }
}
