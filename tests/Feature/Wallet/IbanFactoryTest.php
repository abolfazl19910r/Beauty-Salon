<?php

namespace Tests\Feature\Wallet;

use App\Models\SpecialistWallet;
use App\Models\WithdrawalRequest;
use App\Support\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IbanFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdrawal_request_factory_makes_valid_ibans(): void
    {
        foreach (WithdrawalRequest::factory()->count(200)->make() as $withdrawal) {
            $this->assertTrue(Iban::isValid($withdrawal->iban), $withdrawal->iban);
        }
    }

    public function test_specialist_wallet_factory_has_no_iban_by_default_and_a_valid_one_with_the_state(): void
    {
        $this->assertNull(SpecialistWallet::factory()->make()->iban);

        foreach (SpecialistWallet::factory()->withIban()->count(50)->make() as $wallet) {
            $this->assertTrue(Iban::isValid($wallet->iban), $wallet->iban);
            $this->assertNotEmpty($wallet->account_holder_name);
        }
    }
}
