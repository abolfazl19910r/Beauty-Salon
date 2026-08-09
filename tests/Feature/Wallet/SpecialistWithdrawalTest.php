<?php

namespace Tests\Feature\Wallet;

use App\Models\Specialist;
use App\Models\WalletSetting;
use App\Services\Specialist\SpecialistWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private SpecialistWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SpecialistWalletService::class);
    }

    private function specialistWithBalance(float $balance): Specialist
    {
        $specialist = Specialist::factory()->create();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => $balance, 'iban' => 'IR820540102680020817909002', 'account_holder_name' => 'کاربر تست']);

        return $specialist;
    }

    public function test_withdrawal_below_minimum_amount_is_rejected(): void
    {
        // Regression guard: this must genuinely enforce the configured minimum — the documented
        // WalletSetting::get()/first() bug made this check silently ineffective in the past.
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 100000]);
        $specialist = $this->specialistWithBalance(500000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 50000, 'method' => 'iban']);

        $this->assertFalse($result['success']);
        $this->assertSame(500000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_withdrawal_at_or_above_minimum_amount_succeeds(): void
    {
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 100000, 'withdrawal_fee_percentage' => 0]);
        $specialist = $this->specialistWithBalance(500000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->assertTrue($result['success']);
        $this->assertSame(400000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_withdrawal_above_maximum_amount_is_rejected(): void
    {
        WalletSetting::first()->update(['maximum_withdrawal_amount' => 1000000]);
        $specialist = $this->specialistWithBalance(5000000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 2000000, 'method' => 'iban']);

        $this->assertFalse($result['success']);
    }

    public function test_withdrawal_exceeding_balance_is_rejected(): void
    {
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 10000]);
        $specialist = $this->specialistWithBalance(50000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->assertFalse($result['success']);
    }

    public function test_withdrawal_fee_percentage_is_deducted_for_regular_iban_method(): void
    {
        WalletSetting::first()->update([
            'minimum_withdrawal_amount' => 10000,
            'withdrawal_fee_percentage' => 5,
        ]);
        $specialist = $this->specialistWithBalance(500000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->assertTrue($result['success']);
        $this->assertSame(5000.0, (float) $result['withdrawal_request']->fee);
        $this->assertSame(95000.0, (float) $result['withdrawal_request']->net_amount);
    }

    public function test_creating_a_withdrawal_deducts_the_gross_amount_from_the_wallet_balance(): void
    {
        // The full requested amount (not net-of-fee) must leave the balance immediately —
        // the fee is only relevant to what the specialist actually receives.
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 10000, 'withdrawal_fee_percentage' => 10]);
        $specialist = $this->specialistWithBalance(500000);

        $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->assertSame(400000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_creating_a_withdrawal_sets_status_to_pending(): void
    {
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 10000]);
        $specialist = $this->specialistWithBalance(500000);

        $result = $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->assertSame('pending', $result['withdrawal_request']->status);
    }

    public function test_cancelling_a_withdrawal_refunds_the_wallet(): void
    {
        WalletSetting::first()->update(['minimum_withdrawal_amount' => 10000, 'withdrawal_fee_percentage' => 0]);
        $specialist = $this->specialistWithBalance(500000);
        $result = $this->service->createWithdrawal($specialist, ['amount' => 100000, 'method' => 'iban']);

        $this->service->cancelWithdrawal($specialist, $result['withdrawal_request']);

        $this->assertSame(500000.0, (float) $specialist->wallet->fresh()->balance);
        $this->assertSame('cancelled', $result['withdrawal_request']->fresh()->status);
    }
}
