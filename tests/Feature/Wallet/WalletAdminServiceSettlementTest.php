<?php

namespace Tests\Feature\Wallet;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
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

    /**
     * رگرسیون (۲۰۲۶-۱۰-۱۱): ادمین با «نادیده گرفتن مهلت» تسویه می‌زد و پیام «هیچ تراکنشی یافت نشد»
     * می‌گرفت، چون همه‌ی نوبت‌ها ساعتشان نرسیده بود — حتی نوبتی که متخصص «انجام شد» زده بود.
     */
    public function test_a_completed_booking_settles_with_ignore_delay_even_before_its_time(): void
    {
        [, $specialist] = $this->makePendingIncomeTransaction(
            bookingOverrides: ['booking_time' => now()->addHours(5), 'status' => 'completed'],
            metadataOverrides: ['settlement_date' => now()->addDays(3)->toDateString()]
        );
        $this->makePendingIncomeTransaction(
            bookingOverrides: ['booking_time' => now()->addDays(2), 'status' => 'confirmed'],
        );

        $result = $this->service->settlePendingIncomes(ignoreDelay: true);

        $this->assertSame(1, $result['settledCount']);
        $this->assertSame(1, $result['bookingNotDoneCount']);
        $this->assertSame(50000.0, (float) $specialist->wallet->fresh()->balance);
    }

    public function test_without_ignore_delay_a_completed_booking_still_waits_for_its_settlement_date(): void
    {
        $this->makePendingIncomeTransaction(
            bookingOverrides: ['status' => 'completed'],
            metadataOverrides: ['settlement_date' => now()->addDays(3)->toDateString()]
        );

        $result = $this->service->settlePendingIncomes();

        $this->assertSame(0, $result['settledCount']);
        $this->assertSame(1, $result['notDueCount']);
    }

    public function test_admin_message_explains_why_nothing_was_settled(): void
    {
        [, $specialist] = $this->makePendingIncomeTransaction(
            bookingOverrides: ['booking_time' => now()->addDays(2), 'status' => 'confirmed'],
        );
        $admin = \App\Models\User::factory()->create(['is_admin' => true, 'user_type' => 'staff']);

        $this->actingAs($admin)->post(route('admin.wallet.settle-pending-wallet', $specialist->wallet), ['ignore_delay' => 1])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'هنوز انجام نشده‌اند'));
    }

    /**
     * رگرسیون: «درآمد این ماه» برگشت سهم نوبت لغوشده را کم نمی‌کرد و از «کل درآمد» بیشتر می‌شد.
     */
    public function test_month_income_is_net_of_cancelled_booking_reversals_but_not_manual_adjustments(): void
    {
        [$transaction, $specialist] = $this->makePendingIncomeTransaction();
        $this->makePendingIncomeTransaction(); // سهم متخصص دیگر، نباید حساب شود
        $wallet = $specialist->wallet->fresh();
        $second = Booking::factory()->create(['specialist_id' => $specialist->id, 'booking_time' => now()->subDays(5)->setTime(10, 0)]);
        $wallet->addIncome(30000, $second->id);
        $wallet->reverseIncome($transaction);
        $wallet->transactions()->create(['type' => 'adjustment', 'amount' => 7000, 'balance_after' => 0, 'description' => 'تعدیل دستی']);

        $overview = app(\App\Services\Specialist\SpecialistWalletService::class)->getWalletOverview($specialist->fresh());

        $this->assertSame(30000.0, (float) $overview['currentMonthIncome']);
        $this->assertSame(30000.0, (float) $wallet->fresh()->total_earned);
    }
}
