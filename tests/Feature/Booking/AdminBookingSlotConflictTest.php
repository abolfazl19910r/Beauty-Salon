<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingNotAvailableException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict): dedicated coverage for the two BookingService
 * methods this branch added — createManualBooking() (commit 2) and
 * assertManualRescheduleAvailable() (commit 4) — plus a direct test of the DB-level unique
 * constraint (migration 2026_08_29_000001) independent of either method, to prove the
 * database itself refuses two active bookings on the same specialist+time even if some future
 * code path bypasses the application-level check entirely.
 */
class AdminBookingSlotConflictTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

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

    // ── createManualBooking() ────────────────────────────────────────────

    public function test_create_manual_booking_succeeds_on_an_open_slot(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $user = User::factory()->create();

        $booking = $this->service->createManualBooking([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'phone',
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'source' => 'phone']);
    }

    public function test_create_manual_booking_throws_when_slot_already_taken(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'confirmed',
        ]);

        $this->expectException(BookingNotAvailableException::class);

        $this->service->createManualBooking([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => User::factory()->create()->id,
            'booking_time' => $bookingTime,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'walk_in',
        ]);
    }

    public function test_create_manual_booking_throws_when_specialist_has_no_schedule(): void
    {
        $specialist = Specialist::factory()->create(); // deliberately no schedule
        $service = BeautyService::factory()->create();

        $this->expectException(BookingNotAvailableException::class);

        $this->service->createManualBooking([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => User::factory()->create()->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'phone',
        ]);
    }

    // ── assertManualRescheduleAvailable() ────────────────────────────────

    public function test_assert_manual_reschedule_available_passes_when_slot_is_free(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => now()->addDays(2)->setTime(9, 0),
        ]);

        // No exception = pass. The new time (14:00) is free on this fully-available specialist.
        $this->service->assertManualRescheduleAvailable(
            $booking,
            $specialist->id,
            now()->addDays(2)->setTime(14, 0)->format('Y-m-d H:i:s')
        );

        $this->assertTrue(true);
    }

    public function test_assert_manual_reschedule_available_excludes_the_booking_s_own_slot(): void
    {
        // ⭐ The core edge case this method exists for: re-validating a booking against its own
        // exact current specialist+time must NOT throw "slot taken" just because that booking
        // itself already occupies it.
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $currentTime = now()->addDays(2)->setTime(10, 0);

        $booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $currentTime,
            'status' => 'pending',
        ]);

        $this->service->assertManualRescheduleAvailable(
            $booking,
            $specialist->id,
            $currentTime->format('Y-m-d H:i:s')
        );

        $this->assertTrue(true);
    }

    public function test_assert_manual_reschedule_available_throws_when_another_booking_owns_the_slot(): void
    {
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $contestedTime = now()->addDays(2)->setTime(14, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $contestedTime,
            'status' => 'confirmed',
        ]);

        $bookingBeingEdited = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => now()->addDays(2)->setTime(9, 0),
        ]);

        $this->expectException(BookingNotAvailableException::class);

        $this->service->assertManualRescheduleAvailable($bookingBeingEdited, $specialist->id, $contestedTime);
    }

    // ── DB-level unique constraint (migration 2026_08_29_000001) ─────────

    public function test_database_rejects_two_active_bookings_on_the_exact_same_slot(): void
    {
        // Bypasses BookingService entirely — proves the constraint itself is the real last
        // line of defense, not just the application-level check duplicating the same rule.
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'confirmed',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'pending',
        ]);
    }

    public function test_database_allows_a_cancelled_booking_to_share_a_slot_with_an_active_one(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'cancelled',
        ]);

        // Should NOT throw — a cancelled booking's active_slot_key is NULL, and multiple NULLs
        // never collide in a unique index (true on MySQL, SQLite, and Postgres alike).
        $booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_isDuplicateActiveSlotError_recognizes_the_constraint_violation(): void
    {
        // Regression guard for the earlier mistake in this same branch: an exact-index-name
        // string match ('bookings_active_slot_unique') matches MySQL's error message but NOT
        // SQLite's ("UNIQUE constraint failed: bookings.active_slot_key") — this project's own
        // test suite runs on SQLite (phpunit.xml), so that bug would have made every duplicate
        // insert here surface as an unhandled QueryException instead of BookingNotAvailableException.
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'confirmed',
        ]);

        try {
            DB::table('bookings')->insert([
                'salon_id' => $specialist->salon_id,
                'specialist_id' => $specialist->id,
                'service_id' => $service->id,
                'user_id' => User::factory()->create()->id,
                'booking_time' => $bookingTime,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'source' => 'phone',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Expected a QueryException from the unique constraint.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertTrue($this->service->isDuplicateActiveSlotError($e));
        }
    }
}
