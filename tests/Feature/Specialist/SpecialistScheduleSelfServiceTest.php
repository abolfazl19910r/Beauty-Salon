<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SpecialistProfileController::updateSchedule() (the specialist's own self-service
 * schedule form, as opposed to Admin\Specialist\AdminSpecialistScheduleController) had no
 * dedicated behavior test beyond the bare authorization check in
 * SpecialistSelfServiceAuthorizationTest. This covers the actual save behavior, including
 * the break_start/break_end fields whose schema was completed in test-writing session 9
 * (previously inert — see Specialist::getAvailableSlots() and SpecialistAvailabilityTest).
 */
class SpecialistScheduleSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingSpecialist(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create([
            'phone' => '09121234567',
            'user_id' => $user->id,
        ]);

        return [$user, $specialist];
    }

    public function test_update_persists_an_optional_break_time(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '18:00',
                    'break_start' => '13:00', 'break_end' => '14:00',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('specialist_schedules', [
            'specialist_id' => $specialist->id,
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);
    }

    public function test_update_allows_omitting_the_break_time_entirely(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                ['day_of_week' => 6, 'is_active' => '1', 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('specialist_schedules', [
            'specialist_id' => $specialist->id,
            'break_start' => null,
            'break_end' => null,
        ]);
    }

    public function test_update_rejects_a_break_start_without_a_break_end(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '18:00',
                    'break_start' => '13:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('specialist_schedules', ['specialist_id' => $specialist->id]);
    }

    public function test_update_rejects_a_break_period_that_ends_after_working_hours(): void
    {
        [$user, ] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '17:00',
                    'break_start' => '16:30', 'break_end' => '17:30',
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_update_rejects_a_break_start_before_working_hours_begin(): void
    {
        [$user, ] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '17:00',
                    'break_start' => '08:00', 'break_end' => '08:30',
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_update_replaces_the_entire_schedule_set(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        // First save an active Saturday...
        $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [['day_of_week' => 6, 'is_active' => '1', 'start_time' => '09:00', 'end_time' => '17:00']],
        ]);
        $this->assertDatabaseHas('specialist_schedules', ['specialist_id' => $specialist->id, 'day_of_week' => 6]);

        // ...then replace it entirely with Sunday instead.
        $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [['day_of_week' => 0, 'is_active' => '1', 'start_time' => '10:00', 'end_time' => '18:00']],
        ]);

        $this->assertDatabaseMissing('specialist_schedules', ['specialist_id' => $specialist->id, 'day_of_week' => 6]);
        $this->assertDatabaseHas('specialist_schedules', ['specialist_id' => $specialist->id, 'day_of_week' => 0]);
    }
}
