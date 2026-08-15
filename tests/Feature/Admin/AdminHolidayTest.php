<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHolidayTest extends TestCase
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

    public function test_index_lists_holidays_ordered_by_date(): void
    {
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->addDays(10)]);
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->addDays(2)]);

        $response = $this->actingAs($this->admin)->getJson("/admin/specialists/{$this->specialist->id}/holidays");

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertTrue($data[0]['date'] < $data[1]['date']);
    }

    public function test_store_creates_a_holiday(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => now()->addDays(5)->format('Y-m-d'),
            'description' => 'تعطیلی رسمی',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('holidays', ['specialist_id' => $this->specialist->id, 'description' => 'تعطیلی رسمی']);
    }

    public function test_store_rejects_a_past_date(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date');
    }

    public function test_store_rejects_a_date_that_overlaps_an_approved_leave(): void
    {
        Leave::factory()->create([
            'specialist_id' => $this->specialist->id,
            'status' => 'approved',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(8),
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => now()->addDays(6)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date');
    }

    public function test_store_rejects_a_date_that_has_an_active_booking(): void
    {
        $date = now()->addDays(5);
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'booking_time' => $date,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => $date->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date');
    }

    public function test_store_allows_a_date_that_has_only_a_cancelled_booking(): void
    {
        $date = now()->addDays(5);
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'booking_time' => $date,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => $date->format('Y-m-d'),
        ]);

        $response->assertCreated();
    }

    public function test_store_rejects_a_duplicate_date(): void
    {
        $date = now()->addDays(5)->format('Y-m-d');
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => $date]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays", [
            'date' => $date,
        ]);

        $response->assertStatus(422);
    }

    public function test_destroy_removes_a_future_holiday(): void
    {
        $holiday = Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->addDays(5)]);

        $response = $this->actingAs($this->admin)->deleteJson("/admin/specialists/{$this->specialist->id}/holidays/{$holiday->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_destroy_refuses_to_remove_a_past_holiday(): void
    {
        $holiday = Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->subDays(5)]);

        $response = $this->actingAs($this->admin)->deleteJson("/admin/specialists/{$this->specialist->id}/holidays/{$holiday->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('holidays', ['id' => $holiday->id]);
    }

    public function test_destroy_refuses_a_holiday_belonging_to_a_different_specialist(): void
    {
        $otherSpecialist = Specialist::factory()->create();
        $holiday = Holiday::factory()->create(['specialist_id' => $otherSpecialist->id, 'date' => now()->addDays(5)]);

        $response = $this->actingAs($this->admin)->deleteJson("/admin/specialists/{$this->specialist->id}/holidays/{$holiday->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('holidays', ['id' => $holiday->id]);
    }

    public function test_upcoming_only_returns_future_holidays(): void
    {
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->addDays(5)]);
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => now()->subDays(5)]);

        $response = $this->actingAs($this->admin)->getJson("/admin/specialists/{$this->specialist->id}/holidays/upcoming");

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_check_date_reports_whether_a_date_is_a_holiday(): void
    {
        $date = now()->addDays(5)->format('Y-m-d');
        Holiday::factory()->create(['specialist_id' => $this->specialist->id, 'date' => $date]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays/check", [
            'date' => $date,
        ]);

        $response->assertOk();
        $response->assertJson(['is_holiday' => true]);

        $response = $this->actingAs($this->admin)->postJson("/admin/specialists/{$this->specialist->id}/holidays/check", [
            'date' => now()->addDays(20)->format('Y-m-d'),
        ]);
        $response->assertJson(['is_holiday' => false]);
    }

    public function test_non_admin_cannot_access_holiday_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->getJson("/admin/specialists/{$this->specialist->id}/holidays")->assertStatus(403);
    }
}
