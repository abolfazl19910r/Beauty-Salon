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

    // NOTE: SpecialistSchedule::break_start/break_end are read by getAvailableSlots() but these
    // columns do not exist anywhere in the schema (confirmed via a full grep across migrations,
    // the model, and admin views) — the "break time" branch is entirely inert/unreachable dead
    // code, not a live feature. No test is written for it here since there's nothing to exercise;
    // flagged in Rasta_unified_prompt.md as a cleanup candidate rather than "fixed" in this phase.

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
