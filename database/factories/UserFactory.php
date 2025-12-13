<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_admin' => false,
            'verification_code' => null,
            'verification_code_expire_at' => null,
            'login_verification_code' => null,
            'login_verification_code_expire_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    public function withActiveOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->addMinutes(2),
        ]);
    }

    public function withActiveLoginOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_verification_code' => '987654',
            'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);
    }
}
