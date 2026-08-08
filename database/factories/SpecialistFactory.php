<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialistFactory extends Factory
{
    protected $model = Specialist::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        $specialistRole = Role::where('name', 'specialist')->first();
        if ($specialistRole) {
            $user->roles()->attach($specialistRole);
        }

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => fake()->unique()->safeEmail(),
            'auto_confirm_bookings' => fake()->boolean(20),
        ];
    }

    public function autoConfirm(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_confirm_bookings' => true,
        ]);
    }

    public function manualConfirm(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_confirm_bookings' => false,
        ]);
    }
}
