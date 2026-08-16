<?php

namespace Database\Factories;

use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ⭐ Discovered missing in test-writing session 6 (2026-08-16) — see UserWalletFactory
 * for the full explanation of this recurring pattern.
 */
class UserWalletTransactionFactory extends Factory
{
    protected $model = UserWalletTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => UserWallet::factory(),
            'booking_id' => null,
            'type' => fake()->randomElement(['deposit', 'payment', 'refund']),
            'amount' => fake()->numberBetween(10000, 500000),
            'balance_after' => fake()->numberBetween(10000, 1000000),
            'description' => fake()->sentence(),
            'metadata' => [],
        ];
    }
}
