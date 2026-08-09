<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingNotAvailableException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

    /**
     * A specialist with a schedule open every day of the week, 08:00-20:00, so any future
     * weekday/time within that window is a valid, available slot for booking tests.
     */
    private function makeFullyAvailableSpecialist(): Specialist
    {
        $specialist = Specialist::factory()->manualConfirm()->create();

        for ($day = 0; $day <= 6; $day++) {
            SpecialistSchedule::factory()->create([
                'specialist_id' => $specialist->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '20:00',
                'is_active' => true,
            ]);
        }

        return $specialist;
    }

    private function nextAvailableDateTime(): string
    {
        return now()->addDays(3)->setTime(10, 0, 0)->format('Y-m-d H:i:s');
    }

    // ── createBooking() ──────────────────────────────────────────────────

    public function test_create_booking_uses_the_real_service_price_for_prepayment(): void
    {
        WalletSetting::first()->update(['prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000]);

        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create(['price' => 250000]);
        $user = User::factory()->create();

        $booking = $this->service->createBooking(
            $user->id,
            $service->id,
            $specialist->id,
            $this->nextAvailableDateTime()
        );

        // 30% of 250,000 = 75,000 — must reflect the *service's own* price, not a flat constant.
        $this->assertSame(75000.0, (float) $booking->prepayment_amount);
        $this->assertSame('unpaid', $booking->payment_status);
    }

    public function test_create_booking_auto_confirms_when_specialist_has_auto_confirm_enabled(): void
    {
        $specialist = Specialist::factory()->autoConfirm()->create();
        for ($day = 0; $day <= 6; $day++) {
            SpecialistSchedule::factory()->create([
                'specialist_id' => $specialist->id, 'day_of_week' => $day,
                'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true,
            ]);
        }
        $service = BeautyService::factory()->create(['price' => 100000]);
        $user = User::factory()->create();

        $booking = $this->service->createBooking($user->id, $service->id, $specialist->id, $this->nextAvailableDateTime());

        $this->assertSame('confirmed', $booking->status);
    }

    public function test_create_booking_without_auto_confirm_is_pending_payment(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create(['price' => 100000]);
        $user = User::factory()->create();

        $booking = $this->service->createBooking($user->id, $service->id, $specialist->id, $this->nextAvailableDateTime());

        $this->assertSame('pending_payment', $booking->status);
    }

    public function test_create_booking_throws_when_slot_is_not_available(): void
    {
        $specialist = Specialist::factory()->create(); // no schedule at all -> nothing is available
        $service = BeautyService::factory()->create(['price' => 100000]);
        $user = User::factory()->create();

        $this->expectException(BookingNotAvailableException::class);

        $this->service->createBooking($user->id, $service->id, $specialist->id, $this->nextAvailableDateTime());
    }

    public function test_create_booking_with_discount_code_does_not_reduce_the_prepayment(): void
    {
        WalletSetting::first()->update(['prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000]);

        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create(['price' => 250000]);
        $user = User::factory()->create();
        DiscountCode::factory()->create([
            'code' => 'SAVE10', 'type' => 'percentage', 'amount' => 10,
            'is_active' => true, 'max_uses' => 10, 'used_count' => 0, 'user_id' => null,
        ]);

        $booking = $this->service->createBooking(
            $user->id, $service->id, $specialist->id, $this->nextAvailableDateTime(), 'SAVE10'
        );

        // Prepayment (75,000) must remain untouched by the discount; the discount only affects
        // the "remaining" amount paid in person, per the documented 2026-08-03 pricing redesign.
        $this->assertSame(75000.0, (float) $booking->prepayment_amount);
        $this->assertSame(7500.0, (float) $booking->discount_amount); // 10% of 75,000
        $this->assertSame(167500.0, $booking->remaining_amount); // 250000 - 75000 - 7500
    }

    public function test_create_booking_increments_discount_code_usage(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create(['price' => 100000]);
        $user = User::factory()->create();
        $code = DiscountCode::factory()->create([
            'code' => 'ONCE', 'type' => 'fixed', 'amount' => 5000,
            'is_active' => true, 'max_uses' => 5, 'used_count' => 0, 'user_id' => null,
        ]);

        $this->service->createBooking($user->id, $service->id, $specialist->id, $this->nextAvailableDateTime(), 'ONCE');

        $this->assertSame(1, $code->fresh()->used_count);
    }

    // ── applyDiscountCode() ──────────────────────────────────────────────

    public function test_apply_discount_code_succeeds_on_an_unpaid_booking_without_existing_discount(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'prepayment_amount' => 60000,
            'payment_status' => 'unpaid',
            'discount_code' => null,
        ]);
        DiscountCode::factory()->create([
            'code' => 'DISC20', 'type' => 'percentage', 'amount' => 20,
            'is_active' => true, 'max_uses' => 10, 'used_count' => 0, 'user_id' => null,
        ]);

        $result = $this->service->applyDiscountCode($booking, 'DISC20');

        $this->assertTrue($result['success']);
        $this->assertSame(12000.0, (float) $booking->fresh()->discount_amount); // 20% of 60,000
        $this->assertSame(60000.0, (float) $booking->fresh()->prepayment_amount); // untouched
    }

    public function test_apply_discount_code_rejects_a_code_belonging_to_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherUser->id, 'payment_status' => 'unpaid', 'discount_code' => null]);
        DiscountCode::factory()->create([
            'code' => 'PRIVATE', 'type' => 'fixed', 'amount' => 10000,
            'is_active' => true, 'max_uses' => 10, 'used_count' => 0, 'user_id' => $owner->id,
        ]);

        $result = $this->service->applyDiscountCode($booking, 'PRIVATE');

        $this->assertFalse($result['success']);
        $this->assertNull($booking->fresh()->discount_code);
    }

    public function test_apply_discount_code_rejects_reapplying_on_a_booking_that_already_has_one(): void
    {
        $booking = Booking::factory()->create([
            'payment_status' => 'unpaid',
            'discount_code' => 'ALREADY',
            'discount_amount' => 5000,
        ]);
        DiscountCode::factory()->create([
            'code' => 'NEWCODE', 'type' => 'fixed', 'amount' => 10000,
            'is_active' => true, 'max_uses' => 10, 'used_count' => 0, 'user_id' => null,
        ]);

        $result = $this->service->applyDiscountCode($booking, 'NEWCODE');

        $this->assertFalse($result['success']);
        $this->assertSame('ALREADY', $booking->fresh()->discount_code);
        $this->assertSame(5000.0, (float) $booking->fresh()->discount_amount);
    }

    public function test_apply_discount_code_rejects_application_on_an_already_paid_booking(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'paid', 'discount_code' => null]);
        DiscountCode::factory()->create([
            'code' => 'TOOLATE', 'type' => 'fixed', 'amount' => 10000,
            'is_active' => true, 'max_uses' => 10, 'used_count' => 0, 'user_id' => null,
        ]);

        $result = $this->service->applyDiscountCode($booking, 'TOOLATE');

        $this->assertFalse($result['success']);
        $this->assertNull($booking->fresh()->discount_code);
    }

    public function test_apply_discount_code_rejects_an_invalid_or_unknown_code(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'unpaid', 'discount_code' => null]);

        $result = $this->service->applyDiscountCode($booking, 'DOES-NOT-EXIST');

        $this->assertFalse($result['success']);
    }

    public function test_apply_discount_code_does_not_allow_using_it_beyond_max_uses(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'unpaid', 'discount_code' => null]);
        DiscountCode::factory()->create([
            'code' => 'MAXEDOUT', 'type' => 'fixed', 'amount' => 5000,
            'is_active' => true, 'max_uses' => 3, 'used_count' => 3, 'user_id' => null,
        ]);

        $result = $this->service->applyDiscountCode($booking, 'MAXEDOUT');

        $this->assertFalse($result['success']);
    }

    // ── cancelBooking() ──────────────────────────────────────────────────

    public function test_cancel_booking_sets_cancelled_by_customer(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);

        $this->service->cancelBooking($booking);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('customer', $booking->cancelled_by);
        $this->assertNotNull($booking->cancelled_at);
    }

    public function test_cancel_booking_on_a_paid_booking_triggers_the_wallet_refund(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create();
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'prepayment_amount' => 60000,
        ]);
        $booking->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        $this->service->cancelBooking($booking->fresh());

        $this->assertSame(60000.0, (float) $user->wallet->fresh()->balance);
    }
}
