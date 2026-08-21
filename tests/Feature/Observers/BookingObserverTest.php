<?php

namespace Tests\Feature\Observers;

use App\Models\AdminWallet;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingObserver holds the most financially critical logic in the project: commission split on
 * payment, and refund/penalty distribution on cancellation (per actor: customer/specialist/admin).
 * These tests exercise every branch documented in Rasta_unified_prompt.md under R-Events,
 * R-Observers, and the two "رفع مستقل" cancellation-penalty sessions (2026-07-27/28/29).
 */
class BookingObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidBooking(array $overrides = []): Booking
    {
        $service = BeautyService::factory()->create(['price' => 250000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $user = User::factory()->create();

        $booking = Booking::factory()->create(array_merge([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'prepayment_amount' => 75000,
            // Explicitly pinned far outside any cancellation-fee window (default
            // WalletSetting::cancellation_before_hours is 24h). BookingFactory's
            // default 'booking_time' is random between +1 day and +2 months with the
            // hour-of-day separately randomized to 9-17 — that hour override can
            // legitimately push the effective time to LESS than 24h from now
            // (e.g. "+1 day" landing at 09:00 when 'now' is already past 09:00 the
            // day before), which made customer-cancellation-fee tests that don't care
            // about the fee window flaky depending on the time of day the suite runs.
            // Tests that specifically exercise the fee-window boundary already pass
            // their own explicit 'booking_time' override, which still wins here.
            'booking_time' => now()->addDays(10),
        ], $overrides));

        // Trigger the 'updated' observer path exactly like a real payment does.
        $booking->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        return $booking->fresh();
    }

    // ── Payment: income + commission split ───────────────────────────────

    public function test_payment_splits_prepayment_into_specialist_income_and_admin_commission(): void
    {
        $booking = $this->makePaidBooking();
        $specialist = $booking->specialist;

        // commission_rate = 10% of 75,000 -> admin gets 7,500, specialist gets 67,500.
        $this->assertSame(67500.0, (float) $specialist->wallet->pending_amount);
        $this->assertSame(67500.0, (float) $specialist->wallet->total_earned);
        $this->assertSame(7500.0, (float) AdminWallet::getWallet()->balance);
    }

    public function test_payment_uses_specialists_own_commission_rate_not_global_default(): void
    {
        WalletSetting::first()->update(['admin_commission_percentage' => 50]);

        $booking = $this->makePaidBooking(); // specialist has commission_rate = 10, explicit override

        // Must use 10% (specialist override), not the 50% global default.
        $this->assertSame(67500.0, (float) $booking->specialist->wallet->pending_amount);
    }

    public function test_payment_processing_is_idempotent_and_never_double_credits(): void
    {
        $booking = $this->makePaidBooking();

        // Simulate a second, redundant save that still reports payment_status = 'paid' (e.g. a
        // stray ->save() elsewhere in the request lifecycle). wasChanged() would be false here
        // since payment_status isn't actually changing, so this alone wouldn't double-fire — but
        // explicitly re-triggering by toggling back and forth verifies the cache guard actually
        // works, not just that Eloquent's dirty-tracking happens to protect us.
        $booking->update(['payment_status' => 'unpaid']);
        $booking->update(['payment_status' => 'paid']);

        // The idempotency cache guard (180s TTL) must have blocked the second credit.
        $this->assertSame(67500.0, (float) $booking->specialist->wallet->fresh()->pending_amount);
        $this->assertSame(7500.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    public function test_payment_awards_loyalty_points_using_configured_points_per_amount(): void
    {
        $booking = $this->makePaidBooking(); // prepayment 75,000, default points_per_amount 10,000

        // 5 + floor(75000/10000) = 5 + 7 = 12
        $point = LoyaltyPoint::where('user_id', $booking->user_id)->where('booking_id', $booking->id)->first();

        $this->assertNotNull($point);
        $this->assertSame(12, $point->points);
    }

    // ── Cancellation: customer ───────────────────────────────────────────

    public function test_customer_cancellation_within_penalty_window_deducts_fee_and_refunds_rest(): void
    {
        WalletSetting::first()->update([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        $booking = $this->makePaidBooking(['booking_time' => now()->addHours(5)]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancelled_at' => now(),
        ]);
        $booking->refresh();

        // 20% of 75,000 = 15,000 fee -> customer gets back 60,000.
        $this->assertSame(60000.0, (float) $booking->user->wallet->fresh()->balance);
        $this->assertSame('refunded', $booking->refund_status);
        $this->assertSame(60000.0, (float) $booking->refunded_amount);

        // 100% of the fee goes to the admin wallet as a "penalty", on top of the original 7,500
        // commission it already had reversed and re-earned via the fee itself.
        // Original commission (7,500) was reversed (-7,500), then fee (15,000) was added: net 15,000.
        $this->assertSame(15000.0, (float) AdminWallet::getWallet()->fresh()->balance);

        // Specialist's original income share must be fully reversed (net zero for the specialist).
        $this->assertSame(0.0, (float) $booking->specialist->wallet->fresh()->pending_amount);
    }

    public function test_customer_cancellation_outside_penalty_window_refunds_in_full(): void
    {
        WalletSetting::first()->update([
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
        ]);

        $booking = $this->makePaidBooking(['booking_time' => now()->addDays(5)]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancelled_at' => now(),
        ]);
        $booking->refresh();

        $this->assertSame(75000.0, (float) $booking->user->wallet->fresh()->balance);
        $this->assertSame(75000.0, (float) $booking->refunded_amount);

        // No fee -> admin's original 7,500 commission must be fully clawed back (net zero).
        $this->assertSame(0.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    // ── Cancellation: specialist ─────────────────────────────────────────

    public function test_specialist_cancellation_within_penalty_window_nets_specialist_to_zero(): void
    {
        WalletSetting::first()->update([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 0,
        ]);

        $booking = $this->makePaidBooking(['booking_time' => now()->addHours(5)]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'specialist',
            'cancelled_at' => now(),
        ]);
        $booking->refresh();

        // 10% of 75,000 = 7,500 penalty -> customer gets back 67,500.
        $this->assertSame(67500.0, (float) $booking->user->wallet->fresh()->balance);

        // Specialist's original income share was fully reversed, and no separate deduction is
        // taken from the specialist's wallet for the penalty (it comes out of the customer's
        // refund instead) — specialist nets exactly zero, regardless of the penalty size.
        $this->assertSame(0.0, (float) $booking->specialist->wallet->fresh()->pending_amount);
        $this->assertSame(0.0, (float) $booking->specialist->wallet->fresh()->balance);

        // The 7,500 penalty lands entirely in the admin wallet; the original 7,500 commission is
        // also reversed, but since the penalty is added before the reversal happens, net balance
        // ends up equal to the penalty itself (7,500), not zero.
        $this->assertSame(7500.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    public function test_specialist_cancellation_outside_penalty_window_has_no_penalty(): void
    {
        WalletSetting::first()->update([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
        ]);

        $booking = $this->makePaidBooking(['booking_time' => now()->addDays(10)]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'specialist',
            'cancelled_at' => now(),
        ]);
        $booking->refresh();

        $this->assertSame(75000.0, (float) $booking->user->wallet->fresh()->balance);
        $this->assertSame(0.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    public function test_specialist_repeat_cancellation_increases_penalty(): void
    {
        WalletSetting::first()->update([
            'specialist_cancellation_before_hours' => 24,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_repeat_cancellation_threshold' => 1,
            'specialist_repeat_cancellation_window_days' => 30,
            'specialist_repeat_cancellation_extra_percentage' => 15,
        ]);

        $service = BeautyService::factory()->create(['price' => 250000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);

        // First cancellation by this specialist -> counts toward the "recent cancellations" tally
        // (threshold=1 is already met by this very cancellation, since the count query runs after
        // cancelled_at is set, including this one).
        $firstBooking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'prepayment_amount' => 75000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
        $firstBooking->update(['payment_status' => 'paid', 'status' => 'confirmed']);
        $firstBooking->update(['booking_time' => now()->addHours(5)]);
        $firstBooking->update(['status' => 'cancelled', 'cancelled_by' => 'specialist', 'cancelled_at' => now()]);

        // 10% + 15% (threshold of 1 met by this cancellation itself) = 25% of 75,000 = 18,750.
        $refund = (float) $firstBooking->fresh()->refunded_amount;
        $this->assertSame(56250.0, $refund); // 75000 - 18750
    }

    // ── Cancellation: admin ───────────────────────────────────────────────

    public function test_admin_cancellation_refunds_in_full_with_no_penalty(): void
    {
        $booking = $this->makePaidBooking();

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'admin',
            'cancelled_at' => now(),
        ]);
        $booking->refresh();

        $this->assertSame(75000.0, (float) $booking->user->wallet->fresh()->balance);
        $this->assertSame(75000.0, (float) $booking->refunded_amount);
        $this->assertSame(0.0, (float) AdminWallet::getWallet()->fresh()->balance);
        $this->assertSame(0.0, (float) $booking->specialist->wallet->fresh()->pending_amount);
    }

    // ── Cancellation guards ────────────────────────────────────────────────

    public function test_cancelling_an_unpaid_booking_does_not_touch_any_wallet(): void
    {
        $service = BeautyService::factory()->create(['price' => 250000]);
        $specialist = Specialist::factory()->create();
        $user = User::factory()->create();

        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'prepayment_amount' => 75000,
        ]);

        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);
        $booking->refresh();

        $this->assertNull($booking->refund_status);
        $this->assertNull($booking->refunded_amount);
        $this->assertSame(0.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    public function test_cancellation_processing_is_idempotent_and_never_double_refunds(): void
    {
        $booking = $this->makePaidBooking();

        $booking->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);

        // Force a second 'cancelled' change to fire wasChanged('status') again.
        $booking->update(['status' => 'pending']);
        $booking->update(['status' => 'cancelled']);

        // Balance must reflect exactly ONE refund (75,000), not two.
        $this->assertSame(75000.0, (float) $booking->user->wallet->fresh()->balance);
    }
}
