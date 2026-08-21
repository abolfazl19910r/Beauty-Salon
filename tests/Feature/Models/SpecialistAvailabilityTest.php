<?php

namespace Tests\Feature\Models;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** A Saturday three weeks from "now", safely in the future regardless of when tests run. */
    private function futureDateOnDayOfWeek(int $dayOfWeek): string
    {
        $date = now()->addWeeks(3);
        while ($date->dayOfWeek !== $dayOfWeek) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    public function test_returns_slots_within_the_scheduled_working_hours(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6); // Saturday
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id,
            'day_of_week' => 6,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame(['09:00', '09:30', '10:00', '10:30'], $slots);
    }

    public function test_returns_no_slots_when_specialist_has_no_schedule_for_that_weekday(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        // Schedule exists, but only for a *different* day of the week.
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id,
            'day_of_week' => ($date && \Carbon\Carbon::parse($date)->dayOfWeek === 0) ? 1 : 0,
            'is_active' => true,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame([], $slots);
    }

    public function test_returns_no_slots_when_schedule_is_inactive(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id,
            'day_of_week' => 6,
            'is_active' => false,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame([], $slots);
    }

    public function test_returns_no_slots_on_an_approved_leave_day(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true,
        ]);
        Leave::factory()->approved()->create([
            'specialist_id' => $specialist->id,
            'start_date' => $date,
            'end_date' => $date,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame([], $slots);
    }

    public function test_a_pending_or_rejected_leave_does_not_block_availability(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '10:00', 'is_active' => true,
        ]);
        Leave::factory()->create([
            'specialist_id' => $specialist->id,
            'start_date' => $date, 'end_date' => $date,
            'status' => 'pending',
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertNotEmpty($slots);
    }

    public function test_returns_no_slots_on_a_holiday(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true,
        ]);
        Holiday::factory()->create(['specialist_id' => $specialist->id, 'date' => $date]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame([], $slots);
    }

    public function test_an_existing_booking_removes_its_conflicting_slot(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create(['duration' => 30]);
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '10:00', 'is_active' => true,
        ]);
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $date.' 09:00:00',
            'status' => 'confirmed',
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertNotContains('09:00', $slots);
        $this->assertContains('09:30', $slots);
    }

    public function test_a_cancelled_booking_does_not_block_its_slot(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create(['duration' => 30]);
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '10:00', 'is_active' => true,
        ]);
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $date.' 09:00:00',
            'status' => 'cancelled',
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertContains('09:00', $slots);
    }

    public function test_a_break_period_removes_its_overlapping_slots(): void
    {
        // ⭐ Feature completion (test-writing session 9): break_start/break_end used to be
        // read by getAvailableSlots() with no matching schema columns, making this branch
        // permanently inert. Now that the columns exist, this is the first real test of it.
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '12:00',
            'break_start' => '10:00', 'break_end' => '11:00',
            'is_active' => true,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertContains('09:00', $slots);
        $this->assertContains('09:30', $slots);
        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('10:30', $slots);
        $this->assertContains('11:00', $slots);
        $this->assertContains('11:30', $slots);
    }

    public function test_a_slot_partially_overlapping_the_start_of_a_break_is_excluded(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create(['duration' => 45]);
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '12:00',
            'break_start' => '10:00', 'break_end' => '11:00',
            'is_active' => true,
        ]);

        // A 45-minute slot starting at 09:30 would run until 10:15, overlapping the first
        // 15 minutes of the 10:00-11:00 break — it must be excluded, not just the exact
        // break_start time.
        $slots = $specialist->getAvailableSlots($date, $service->duration);

        $this->assertNotContains('09:30', $slots);
    }

    public function test_no_break_columns_set_behaves_exactly_like_before_the_schema_completion(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '11:00',
            'is_active' => true,
        ]);

        $slots = $specialist->getAvailableSlots($date, 30);

        $this->assertSame(['09:00', '09:30', '10:00', '10:30'], $slots);
    }

    public function test_a_past_date_always_returns_no_slots(): void
    {
        $specialist = Specialist::factory()->create();
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id,
            'day_of_week' => now()->subDay()->dayOfWeek,
            'is_active' => true,
        ]);

        $slots = $specialist->getAvailableSlots(now()->subDay()->toDateString(), 30);

        $this->assertSame([], $slots);
    }

    public function test_is_available_reflects_whether_the_exact_time_is_free(): void
    {
        $specialist = Specialist::factory()->create();
        $date = $this->futureDateOnDayOfWeek(6);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => 6,
            'start_time' => '09:00', 'end_time' => '10:00', 'is_active' => true,
        ]);

        $this->assertTrue($specialist->isAvailable($date.' 09:00:00', 30));
        $this->assertFalse($specialist->isAvailable($date.' 23:00:00', 30));
    }
}
