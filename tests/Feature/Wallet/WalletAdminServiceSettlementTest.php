<?php

namespace Tests\Feature\Wallet;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\WalletSetting;
use App\Services\Admin\Wallet\WalletAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAdminServiceSettlementTest extends TestCase
{
    use RefreshDatabase;

    private WalletAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletAdminService::class);
    }

    private function makePendingIncomeTransaction(array $bookingOverrides = [], array $metadataOverrides = [])
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $booking = Booking::factory()->create(array_merge([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => now()->subDays(3), // appointment already happened, by default
        ], $bookingOverrides));

        $transaction = $specialist->getOrCreateWallet()->addIncome(50000, $booking->id);

        if ($metadataOverrides) {
            $transaction->update(['metadata' => array_merge($transaction->metadata, $metadataOverrides)]);
        }

        return [$transaction, $specialist, $booking];
    }

    public function test_settles_a_transaction_whose_settlement_date_has_passed(): void
    {
        [$transaction, $specialist] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['settlement_date' => now()->subDay()->toDateString()]
        );

        $result = $this->service->settlePendingIncomes();

        $this->assertSame(1, $result['settledCount']);
        $this->assertSame(0.0, (float) $specialist->wallet->fresh()->pending_amount);
        $this->assertSame(50000.0, (float) $specialist->wallet->fresh()->balance);
        $this->assertSame('settled', $transaction->fresh()->metadata['status']);
    }

    public function test_does_not_settle_a_transaction_whose_settlement_date_is_in_the_future(): void
    {
        [$transaction, $specialist] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['settlement_date' => now()->addDay()->toDateString()]
        );

        $result = $this->service->settlePendingIncomes();

        $this->assertSame(0, $result['settledCount']);
        $this->assertSame(50000.0, (float) $specialist->wallet->fresh()->pending_amount);
    }

    public function test_ignore_delay_still_respects_a_future_booking_time(): void
    {
        // Even with --ignore-delay, a booking whose appointment hasn't happened yet must never
        // settle (documented R-Observers safeguard against withdrawing money for unrendered
        // services).
        [$transaction, $specialist] = $this->makePendingIncomeTransaction(
            bookingOverrides: ['booking_time' => now()->addDays(5)],
            metadataOverrides: ['settlement_date' => now()->addDay()->toDateString()]
        );

        $result = $this->service->settlePendingIncomes(ignoreDelay: true);

        $this->assertSame(0, $result['settledCount']);
        $this->assertSame(50000.0, (float) $specialist->wallet->fresh()->pending_amount);
    }

    public function test_ignore_delay_settles_a_past_booking_regardless_of_settlement_date(): void
    {
        [$transaction, $specialist] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['settlement_date' => now()->addDays(10)->toDateString()]
        );

        $result = $this->service->settlePendingIncomes(ignoreDelay: true);

        $this->assertSame(1, $result['settledCount']);
        $this->assertSame(50000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_reversed_transactions_are_never_settled(): void
    {
        // A transaction whose booking was cancelled and refunded (metadata.status = 'reversed')
        // must be permanently excluded from the settlement query, even with --ignore-delay —
        // otherwise the specialist would be paid twice for a cancelled booking.
        [$transaction, $specialist] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['status' => 'reversed', 'settlement_date' => now()->subDay()->toDateString()]
        );

        $result = $this->service->settlePendingIncomes(ignoreDelay: true);

        $this->assertSame(0, $result['settledCount']);
    }

    public function test_settlement_is_scoped_to_a_single_wallet_when_provided(): void
    {
        [$transactionA, $specialistA] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['settlement_date' => now()->subDay()->toDateString()]
        );
        [$transactionB, $specialistB] = $this->makePendingIncomeTransaction(
            metadataOverrides: ['settlement_date' => now()->subDay()->toDateString()]
        );

        $result = $this->service->settlePendingIncomes(wallet: $specialistA->wallet);

        $this->assertSame(1, $result['settledCount']);
        $this->assertSame(50000.0, (float) $specialistA->wallet->fresh()->balance);
        $this->assertSame(0.0, (float) $specialistB->wallet->fresh()->balance);
    }
}
