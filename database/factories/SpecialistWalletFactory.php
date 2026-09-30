<?php

namespace Database\Factories;

use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Support\Iban;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ⭐ Fix (test-writing session 7): SpecialistWallet has HasFactory but no factory file
 * existed. Same recurring gap pattern documented for Review, ReportExport, UserWallet,
 * UserWalletTransaction, and now WithdrawalRequest.
 */
class SpecialistWalletFactory extends Factory
{
    protected $model = SpecialistWallet::class;

    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
            'pending_amount' => 0,
            'iban_verified' => false,
        ];
    }

    public function withIban(): static
    {
        return $this->state(fn () => [
            'iban' => Iban::fromBban($this->faker->numerify(str_repeat('#', 22))),
            'account_holder_name' => $this->faker->name(),
            'bank_name' => 'بانک ملت',
        ]);
    }
}
