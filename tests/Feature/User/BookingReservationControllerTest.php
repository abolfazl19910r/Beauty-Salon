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
 * BookingReservationController (create/confirm/store/cancel) never had a dedicated HTTP
 * test — BookingServiceTest exercises the underlying service directly, but the
 * routes/Form-Requests/session-handoff-between-confirm-and-store layer was untested.
 */
class BookingReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BeautyService $service;
    private Specialist $specialist;
    private string $bookingTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $this->specialist = Specialist::factory()->create();

        $target = now()->addDay()->setTime(10, 0);
        // Give the specialist a full-day schedule on the target weekday so
        // Specialist::getAvailableSlots() actually offers 10:00 as a free slot.
        SpecialistSchedule::factory()->create([
            'specialist_id' => $this->specialist->id,
            'day_of_week' => $target->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);
        $this->bookingTime = $target->format('Y-m-d H:i:s');
    }

    public function test_create_redirects_guests_to_login(): void
    {
        $response = $this->get('/bookings/create');

        $response->assertRedirect(route('login'));
    }

    public function test_create_renders_the_form_for_authenticated_users(): void
    {
        $response = $this->actingAs($this->user)->get('/bookings/create');

        $response->assertOk();
        $response->assertViewHas('services');
        $response->assertViewHas('specialists');
    }

    public function test_confirm_renders_the_confirmation_page_with_prepayment(): void
    {
        $response = $this->actingAs($this->user)->post('/bookings/confirm', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $response->assertOk();
        $response->assertViewHas('prepaymentAmount');
        $this->assertSame([
            'service_id' => (string) $this->service->id,
            'specialist_id' => (string) $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ], array_map('strval', session('pending_booking')));
    }

    /**
     * ⭐ Fix (item 4 from the follow-up review): confirm.blade.php's discount-code JS was still
     * calling the dead '/api/check-discount' endpoint (removed in an earlier session's cleanup of
     * routes/api/public/bookings.php) instead of the real web route 'bookings.check-discount'.
     * Applying a discount code on this page always failed with "خطا در بررسی کد تخفیف" even for a
     * perfectly valid code, while the same code worked fine on the payment page (which correctly
     * used route('bookings.apply-discount', ...)). This is a rendering-level regression guard so a
     * future change can't silently reintroduce a reference to the removed endpoint.
     */
    public function test_confirm_page_uses_the_real_check_discount_route_not_the_removed_api_endpoint(): void
    {
        $response = $this->actingAs($this->user)->post('/bookings/confirm', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $response->assertOk();
        $response->assertSee(route('bookings.check-discount'), false);
        $response->assertDontSee('/api/check-discount', false);
    }

    public function test_confirm_rejects_an_unavailable_time_slot(): void
    {
        // Someone else already booked this exact slot.
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'booking_time' => $this->bookingTime,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)->post('/bookings/confirm', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_confirm_requires_a_future_booking_time(): void
    {
        $response = $this->actingAs($this->user)->post('/bookings/confirm', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('booking_time');
    }

    public function test_store_creates_a_booking_and_redirects_to_payment(): void
    {
        $response = $this->actingAs($this->user)->post('/bookings', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('payment.show', ['booking' => $booking->id]));
        $this->assertSame($this->user->id, $booking->user_id);
    }

    public function test_store_returns_json_when_the_client_expects_json(): void
    {
        $response = $this->actingAs($this->user)->postJson('/bookings', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'booking']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post('/bookings', [
            'service_id' => $this->service->id,
            'specialist_id' => $this->specialist->id,
            'booking_time' => $this->bookingTime,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_cancel_cancels_a_pending_booking_owned_by_the_user(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'booking_time' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)->put("/bookings/{$booking->id}/cancel");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('customer', $booking->fresh()->cancelled_by);
    }

    public function test_cancel_is_forbidden_for_another_users_booking(): void
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'pending',
            'booking_time' => now()->addDays(2),
        ]);

        $this->actingAs($this->user)->put("/bookings/{$booking->id}/cancel")->assertForbidden();
    }

    public function test_cancel_is_refused_within_24_hours_of_the_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_time' => now()->addHours(2),
        ]);

        $this->actingAs($this->user)->put("/bookings/{$booking->id}/cancel")->assertForbidden();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
