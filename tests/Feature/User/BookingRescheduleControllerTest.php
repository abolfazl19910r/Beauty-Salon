<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingRescheduleController had no dedicated HTTP test — only the reschedule cutoff logic
 * on Booking::canBeRescheduled() was exercised indirectly. This covers the actual reschedule
 * flow: availability re-check, status transition (auto-confirm vs pending-again), ownership,
 * and both the JSON and redirect response shapes the same endpoint supports.
 */
class BookingRescheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Specialist $specialist;
    private BeautyService $service;
    private string $newTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = BeautyService::factory()->create(['duration' => 30]);

        $target = now()->addDays(3)->setTime(11, 0);
        $this->newTime = $target->format('Y-m-d H:i:s');

        $this->specialist = Specialist::factory()->create(['auto_confirm_bookings' => false]);
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'status' => 'confirmed',
            'booking_time' => now()->addDays(5),
        ], $overrides));
    }

    public function test_show_renders_the_reschedule_form_for_the_owner(): void
    {
        $booking = $this->makeBooking();

        $response = $this->actingAs($this->user)->get("/bookings/{$booking->id}/reschedule");

        $response->assertOk();
        $response->assertViewHas('booking');
    }

    public function test_show_is_forbidden_for_another_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = $this->makeBooking(['user_id' => $other->id]);

        $this->actingAs($this->user)->get("/bookings/{$booking->id}/reschedule")->assertForbidden();
    }

    public function test_update_moves_the_booking_to_pending_when_specialist_does_not_auto_confirm(): void
    {
        $booking = $this->makeBooking(['status' => 'confirmed']);

        $response = $this->actingAs($this->user)->put("/bookings/{$booking->id}/reschedule", [
            'booking_time' => $this->newTime,
        ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $response->assertSessionHas('success');
        $booking->refresh();
        $this->assertSame($this->newTime, $booking->booking_time->format('Y-m-d H:i:s'));
        $this->assertSame('pending', $booking->status);
    }

    public function test_update_auto_confirms_when_the_specialist_has_auto_confirm_enabled(): void
    {
        $this->specialist->update(['auto_confirm_bookings' => true]);
        $booking = $this->makeBooking(['status' => 'confirmed']);

        $this->actingAs($this->user)->put("/bookings/{$booking->id}/reschedule", [
            'booking_time' => $this->newTime,
        ]);

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_update_rejects_an_unavailable_slot(): void
    {
        $booking = $this->makeBooking();
        // Someone else already occupies the target slot.
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'booking_time' => $this->newTime,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)->put("/bookings/{$booking->id}/reschedule", [
            'booking_time' => $this->newTime,
        ]);

        $response->assertSessionHas('error');
        $this->assertNotSame($this->newTime, $booking->fresh()->booking_time->format('Y-m-d H:i:s'));
    }

    public function test_update_returns_json_with_a_409_for_an_unavailable_slot_when_json_is_expected(): void
    {
        $booking = $this->makeBooking();
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'booking_time' => $this->newTime,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)->putJson("/bookings/{$booking->id}/reschedule", [
            'booking_time' => $this->newTime,
        ]);

        $response->assertStatus(409);
        $response->assertJson(['success' => false]);
    }

    public function test_update_returns_json_with_a_redirect_url_on_success(): void
    {
        $booking = $this->makeBooking();

        $response = $this->actingAs($this->user)->putJson("/bookings/{$booking->id}/reschedule", [
            'booking_time' => $this->newTime,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('bookings.show', $booking),
        ]);
    }

    public function test_update_is_refused_past_the_24_hour_cutoff(): void
    {
        $booking = $this->makeBooking(['booking_time' => now()->addHours(2)]);

        $this->actingAs($this->user)
            ->put("/bookings/{$booking->id}/reschedule", ['booking_time' => $this->newTime])
            ->assertForbidden();
    }

    public function test_update_is_forbidden_for_another_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = $this->makeBooking(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->put("/bookings/{$booking->id}/reschedule", ['booking_time' => $this->newTime])
            ->assertForbidden();
    }
}
