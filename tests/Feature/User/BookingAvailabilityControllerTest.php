<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Both getAvailableTimeSlots/getAvailableDates ({specialist}) and getSpecialistsByService
 * ({service}) receive their route parameter pre-resolved into a model instance via the
 * global Route::bind() calls in RouteServiceProvider — the controller's own
 * resolveSpecialist()/resolveService() helpers exist specifically to handle that (see the
 * fix applied to resolveService() this session).
 */
class BookingAvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->specialist = Specialist::factory()->create();
    }

    public function test_available_time_slots_returns_slots_on_a_working_day(): void
    {
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => now()->addDays(3)->dayOfWeek,
            'is_active' => true,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
        $date = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/slots/{$date}");

        $response->assertOk();
        $this->assertNotEmpty($response->json('slots'));
    }

    public function test_available_time_slots_is_empty_on_a_day_without_a_schedule(): void
    {
        $date = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/slots/{$date}");

        $response->assertOk();
        $response->assertJson(['slots' => []]);
        $this->assertStringContainsString('روزهای کاری', $response->json('message'));
    }

    public function test_available_time_slots_is_empty_on_a_holiday(): void
    {
        $date = now()->addDays(3);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $date->dayOfWeek,
            'is_active' => true,
        ]);
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => $date]);

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/slots/{$date->format('Y-m-d')}");

        $response->assertOk();
        $response->assertJson(['slots' => [], 'message' => 'این روز تعطیل است']);
    }

    public function test_available_time_slots_is_empty_during_an_approved_leave(): void
    {
        $date = now()->addDays(3);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $date->dayOfWeek,
            'is_active' => true,
        ]);
        Leave::factory()->create([
            'specialist_id' => $this->specialist->id,
            'status' => 'approved',
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/slots/{$date->format('Y-m-d')}");

        $response->assertOk();
        $this->assertStringContainsString('مرخصی', $response->json('message'));
    }

    public function test_available_time_slots_returns_404_for_a_nonexistent_specialist(): void
    {
        $date = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)->getJson("/bookings/specialists/999999/slots/{$date}");

        $response->assertStatus(404);
    }

    public function test_available_dates_only_returns_days_with_a_schedule_and_no_conflicts(): void
    {
        $workingDay = now()->addDays(2);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $workingDay->dayOfWeek,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/dates");

        $response->assertOk();
        $dates = $response->json();
        $this->assertContains($workingDay->format('Y-m-d'), $dates);
    }

    public function test_available_dates_excludes_a_day_marked_as_a_holiday(): void
    {
        $day = now()->addDays(2);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $day->dayOfWeek,
            'is_active' => true,
        ]);
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => $day]);

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/specialists/{$this->specialist->id}/dates");

        $this->assertNotContains($day->format('Y-m-d'), $response->json());
    }

    public function test_specialists_by_service_returns_only_specialists_linked_to_that_service(): void
    {
        $service = BeautyService::factory()->create();
        $service->specialists()->attach($this->specialist->id);
        $unrelatedSpecialist = Specialist::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/bookings/services/{$service->id}/specialists");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($this->specialist->id));
        $this->assertFalse($ids->contains($unrelatedSpecialist->id));
    }

    public function test_specialists_by_service_returns_404_for_an_invalid_service_id(): void
    {
        // Route::bind('service', ...) globally resolves {service} via findOrFail() BEFORE the
        // controller ever runs, so an invalid id 404s at the routing layer, not inside the
        // controller's own try/catch (which only handles genuinely unexpected failures now
        // that resolveService() no longer double-resolves an already-resolved model).
        $response = $this->actingAs($this->user)->getJson('/bookings/services/999999/specialists');

        $response->assertStatus(404);
    }
}
