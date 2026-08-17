<?php

namespace Database\Factories;

use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ⭐ Fix (test-writing session 7): App\Models\WithdrawalRequest has the HasFactory trait
 * but no corresponding factory file existed anywhere in database/factories — the exact
 * same gap pattern already found for Review and ReportExport (session 4) and
 * UserWallet/UserWalletTransaction (session 6). Calling WithdrawalRequest::factory()
 * threw "Class Database\Factories\WithdrawalRequestFactory not found".
 */
class WithdrawalRequestFactory extends Factory
{
    protected $model = WithdrawalRequest::class;

    public function definition(): array
    {
        $specialist = Specialist::factory();
        $amount = $this->faker->numberBetween(50000, 500000);

        return [
            'wallet_id' => SpecialistWallet::factory()->for($specialist),
            'specialist_id' => $specialist,
            'reference_code' => 'WD-'.strtoupper(Str::random(10)),
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'method' => 'iban',
            'iban' => 'IR'.$this->faker->numerify(str_repeat('#', 24)),
            'account_holder_name' => $this->faker->name(),
            'status' => 'pending',
        ];
    }
}
