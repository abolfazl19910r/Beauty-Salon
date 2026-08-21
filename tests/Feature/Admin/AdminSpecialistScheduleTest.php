<?php

namespace Tests\Feature\Admin;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSpecialistScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->specialist = Specialist::factory()->create();
    }

    public function test_edit_groups_existing_schedules_by_day_of_week(): void
    {
        SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id, 'day_of_week' => 1]);
        SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id, 'day_of_week' => 3]);

        $response = $this->actingAs($this->admin)->get("/admin/specialists/{$this->specialist->id}/schedules/edit");

        $response->assertOk();
        $this->assertCount(2, $response->viewData('schedules'));
    }

    public function test_update_replaces_the_entire_schedule_set(): void
    {
        SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id, 'day_of_week' => 1]);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => ['day_of_week' => 6, 'is_active' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                1 => ['day_of_week' => 0, 'is_active' => '1', 'start_time' => '10:00', 'end_time' => '18:00'],
            ],
        ]);

        $response->assertRedirect(route('admin.specialists.show', $this->specialist));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('specialist_schedules', 2);
        $this->assertDatabaseHas('specialist_schedules', [
            'specialist_id' => $this->specialist->id,
            'day_of_week' => 6,
            'start_time' => '09:00',
        ]);
        // Regression guard: the previous day_of_week=1 row must be gone — update() fully
        // replaces the schedule set (delete-then-recreate), not merge/patch.
        $this->assertDatabaseMissing('specialist_schedules', [
            'specialist_id' => $this->specialist->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_update_skips_days_marked_inactive(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => ['day_of_week' => 6, 'is_active' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                1 => ['day_of_week' => 0, 'is_active' => '0', 'start_time' => null, 'end_time' => null],
            ],
        ]);

        $response->assertRedirect(route('admin.specialists.show', $this->specialist));
        $this->assertDatabaseCount('specialist_schedules', 1);
    }

    public function test_update_with_no_schedules_key_clears_all_schedules(): void
    {
        SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id]);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", []);

        $response->assertRedirect(route('admin.specialists.show', $this->specialist));
        $this->assertDatabaseCount('specialist_schedules', 0);
    }

    public function test_update_rejects_an_end_time_before_start_time_on_an_active_day(): void
    {
        // Regression guard: update()'s try/catch previously caught \Exception broadly, which
        // also swallows ValidationException (it extends \Exception) — turning a normal
        // per-field validation redirect into a generic flash message containing the raw,
        // untranslated rule key ("validation.after") instead of a real error. The fix
        // re-throws ValidationException so Laravel's default redirect-with-$errors kicks in.
        $response = $this->actingAs($this->admin)->from(route('admin.specialists.schedules.edit', $this->specialist))
            ->put("/admin/specialists/{$this->specialist->id}/schedules", [
                'schedules' => [
                    0 => ['day_of_week' => 6, 'is_active' => '1', 'start_time' => '17:00', 'end_time' => '09:00'],
                ],
            ]);

        $response->assertSessionHasErrors();
        $this->assertNotSame(
            'خطا در ذخیره اطلاعات: validation.after',
            session('error'),
            'validation errors must not be swallowed into a generic untranslated flash message'
        );
    }

    public function test_non_admin_cannot_access_schedule_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get("/admin/specialists/{$this->specialist->id}/schedules/edit")->assertStatus(403);
    }

    // ── break_start/break_end (schema completed in test-writing session 9) ─────────────

    public function test_update_persists_an_optional_break_time(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '18:00',
                    'break_start' => '13:00', 'break_end' => '14:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.specialists.show', $this->specialist));
        $this->assertDatabaseHas('specialist_schedules', [
            'specialist_id' => $this->specialist->id,
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);
    }

    public function test_update_allows_omitting_the_break_time_entirely(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => ['day_of_week' => 6, 'is_active' => '1', 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
        ]);

        $response->assertRedirect(route('admin.specialists.show', $this->specialist));
        $this->assertDatabaseHas('specialist_schedules', [
            'specialist_id' => $this->specialist->id,
            'break_start' => null,
            'break_end' => null,
        ]);
    }

    public function test_update_rejects_a_break_end_without_a_break_start(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '18:00',
                    'break_end' => '14:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('specialist_schedules', ['specialist_id' => $this->specialist->id]);
    }

    public function test_update_rejects_a_break_period_outside_the_working_hours(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/schedules", [
            'schedules' => [
                0 => [
                    'day_of_week' => 6, 'is_active' => '1',
                    'start_time' => '09:00', 'end_time' => '12:00',
                    'break_start' => '13:00', 'break_end' => '14:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
    }
}
