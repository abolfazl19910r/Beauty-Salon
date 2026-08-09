<?php

namespace Tests\Unit\Models;

use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletSettingTest extends TestCase
{
    use RefreshDatabase;

    // ── calculatePrepaymentAmount() ──────────────────────────────────────────

    public function test_prepayment_uses_percentage_when_above_minimum(): void
    {
        $settings = new WalletSetting([
            'prepayment_percentage' => 30,
            'minimum_prepayment_amount' => 50000,
        ]);

        // 30% of 250,000 = 75,000, above the 50,000 floor.
        $this->assertSame(75000.0, $settings->calculatePrepaymentAmount(250000));
    }

    public function test_prepayment_falls_back_to_minimum_when_percentage_is_lower(): void
    {
        $settings = new WalletSetting([
            'prepayment_percentage' => 30,
            'minimum_prepayment_amount' => 50000,
        ]);

        // 30% of 100,000 = 30,000, below the 50,000 floor -> floor applies.
        $this->assertSame(50000.0, $settings->calculatePrepaymentAmount(100000));
    }

    public function test_prepayment_is_capped_at_the_full_service_price(): void
    {
        // Regression case documented in the project: a cheap service (30,000) must never produce
        // a prepayment (normally the 50,000 floor) that exceeds the service's own total price.
        $settings = new WalletSetting([
            'prepayment_percentage' => 30,
            'minimum_prepayment_amount' => 50000,
        ]);

        $this->assertSame(30000.0, $settings->calculatePrepaymentAmount(30000));
    }

    public function test_prepayment_of_zero_price_service_is_zero(): void
    {
        $settings = new WalletSetting([
            'prepayment_percentage' => 30,
            'minimum_prepayment_amount' => 50000,
        ]);

        $this->assertSame(0.0, $settings->calculatePrepaymentAmount(0));
    }

    // ── calculateCustomerCancellationFee() ───────────────────────────────────

    public function test_customer_cancellation_fee_applies_within_threshold(): void
    {
        $settings = new WalletSetting([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        // Booking is 10 hours away -> within the 24h threshold -> fee applies.
        $fee = $settings->calculateCustomerCancellationFee(100000, now()->addHours(10));

        $this->assertSame(20000.0, $fee);
    }

    public function test_customer_cancellation_fee_is_zero_outside_threshold(): void
    {
        $settings = new WalletSetting([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        // Booking is 48 hours away -> outside the 24h threshold -> no fee.
        $fee = $settings->calculateCustomerCancellationFee(100000, now()->addHours(48));

        $this->assertEquals(0, $fee);
    }

    public function test_customer_cancellation_fee_applies_exactly_at_the_boundary(): void
    {
        // hoursUntilBooking > threshold means "no fee"; exactly-at-threshold should still incur
        // the fee (documented behavior: only strictly *more* time than the threshold is exempt).
        $settings = new WalletSetting([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        $fee = $settings->calculateCustomerCancellationFee(100000, now()->addHours(24));

        $this->assertSame(20000.0, $fee);
    }

    public function test_customer_cancellation_fee_for_a_past_booking_time_still_applies(): void
    {
        // Negative "hours until booking" (already past) must not be treated as "far away" —
        // diffInHours(..., false) returns a negative number here, which is still <= threshold.
        $settings = new WalletSetting([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        $fee = $settings->calculateCustomerCancellationFee(100000, now()->subHours(2));

        $this->assertSame(20000.0, $fee);
    }

    // ── calculateSpecialistCancellationPenalty() ─────────────────────────────

    public function test_specialist_penalty_applies_within_threshold(): void
    {
        $settings = new WalletSetting([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_extra_percentage' => 0,
        ]);

        $penalty = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 0);

        $this->assertSame(10000.0, $penalty);
    }

    public function test_specialist_penalty_is_zero_outside_threshold(): void
    {
        $settings = new WalletSetting([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_extra_percentage' => 0,
        ]);

        $penalty = $settings->calculateSpecialistCancellationPenalty(100000, now()->addDays(5), 0);

        $this->assertEquals(0, $penalty);
    }

    public function test_specialist_repeat_cancellation_adds_extra_percentage_when_threshold_reached(): void
    {
        $settings = new WalletSetting([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 3,
            'specialist_repeat_cancellation_extra_percentage' => 15,
        ]);

        // 2 recent cancellations < threshold of 3 -> base percentage only.
        $penaltyBelowThreshold = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 2);
        $this->assertSame(10000.0, $penaltyBelowThreshold);

        // 3 recent cancellations meets the threshold -> 10% + 15% = 25%.
        $penaltyAtThreshold = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 3);
        $this->assertSame(25000.0, $penaltyAtThreshold);

        // Above the threshold also triggers it (>=, not just ==).
        $penaltyAboveThreshold = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 10);
        $this->assertSame(25000.0, $penaltyAboveThreshold);
    }

    public function test_specialist_repeat_cancellation_feature_is_disabled_when_threshold_is_zero(): void
    {
        // threshold = 0 is documented as "feature disabled" — even with many recent
        // cancellations, no extra percentage should be added.
        $settings = new WalletSetting([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_extra_percentage' => 50,
        ]);

        $penalty = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 99);

        $this->assertSame(10000.0, $penalty);
    }

    public function test_specialist_penalty_percentage_is_capped_at_100(): void
    {
        $settings = new WalletSetting([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 80,
            'specialist_repeat_cancellation_threshold' => 1,
            'specialist_repeat_cancellation_extra_percentage' => 50,
        ]);

        // 80% + 50% = 130%, must be capped at 100% of the amount.
        $penalty = $settings->calculateSpecialistCancellationPenalty(100000, now()->addHours(5), 1);

        $this->assertSame(100000.0, $penalty);
    }

    // ── get() ─────────────────────────────────────────────────────────────

    public function test_get_returns_self_instance_not_a_collection(): void
    {
        // Documented safety note: WalletSetting::get() intentionally overrides Eloquent's static
        // get() to always return a single model instance (never a Collection). This must never be
        // "fixed" back to default Eloquent behavior.
        $settings = WalletSetting::get();

        $this->assertInstanceOf(WalletSetting::class, $settings);
    }
}
