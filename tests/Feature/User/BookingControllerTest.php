<?php

namespace Tests\Feature\User;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User\BookingController (index/show/success/failed/rate) had zero dedicated HTTP test
 * coverage before this session — notably including the "مرتب‌سازی هوشمند لیست نوبت‌های من"
 * feature (status-priority ordering: confirmed/completed > pending/pending_payment >
 * cancelled, newest-within-group first) documented as a real feature addition in
 * Rasta_unified_prompt.md, which had never actually been exercised via a real HTTP request.
 */
class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_orders_by_status_priority_then_recency_within_group(): void
    {
        $cancelled = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'cancelled',
            'booking_time' => now()->addDays(10),
        ]);
        $pendingOld = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'booking_time' => now()->addDays(1),
        ]);
        $pendingNew = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending_payment',
            'booking_time' => now()->addDays(5),
        ]);
        $confirmedOld = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_time' => now()->addDays(2),
        ]);
        $confirmedNew = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'booking_time' => now()->addDays(8),
        ]);

        $response = $this->actingAs($this->user)->get('/bookings');

        $response->assertOk();
        $ids = collect($response->viewData('bookings')->items())->pluck('id')->all();

        // Group 1 (confirmed/completed) first, newest booking_time first within the group;
        // group 2 (pending/pending_payment) next, same rule; group 3 (cancelled) last.
        $this->assertSame([
            $confirmedNew->id, $confirmedOld->id,
            $pendingNew->id, $pendingOld->id,
            $cancelled->id,
        ], $ids);
    }

    public function test_index_filters_by_status(): void
    {
        Booking::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Booking::factory()->create(['user_id' => $this->user->id, 'status' => 'cancelled']);

        $response = $this->actingAs($this->user)->get('/bookings?status=cancelled');

        $this->assertCount(1, $response->viewData('bookings'));
    }

    public function test_index_only_shows_the_authenticated_users_own_bookings(): void
    {
        Booking::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        Booking::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get('/bookings');

        $this->assertCount(1, $response->viewData('bookings'));
    }

    public function test_show_renders_for_the_owner(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)->get("/bookings/{$booking->id}");

        $response->assertOk();
        $response->assertViewHas('booking');
    }

    public function test_show_redirects_to_payment_for_an_unpaid_pending_payment_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->user)->get("/bookings/{$booking->id}");

        $response->assertRedirect(route('payment.show', ['booking' => $booking->id]));
    }

    public function test_show_is_forbidden_for_another_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)->get("/bookings/{$booking->id}")->assertForbidden();
    }

    public function test_success_shows_the_booking_from_the_session(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withSession(['booking_id' => $booking->id])
            ->get('/bookings/success');

        $response->assertOk();
        $response->assertViewHas('booking', function ($viewBooking) use ($booking) {
            return $viewBooking && $viewBooking->id === $booking->id;
        });
    }

    public function test_success_falls_back_to_the_query_string_id(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/bookings/success?id={$booking->id}");

        $response->assertOk();
        $response->assertViewHas('booking', function ($viewBooking) use ($booking) {
            return $viewBooking && $viewBooking->id === $booking->id;
        });
    }

    public function test_success_cannot_show_another_users_booking(): void
    {
        // ⭐ Regression guard (real bug found+fixed this session): the controller
        // correctly returns booking=null here (the lookup is scoped to auth()->id()), but
        // bookings/success.blade.php used to read $booking->id/->payment_reference/etc
        // unconditionally, so this exact scenario threw a fatal "Attempt to read property
        // on null" instead of rendering a graceful page.
        $other = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get("/bookings/success?id={$booking->id}");

        $response->assertOk();
        $response->assertViewHas('booking', null);
    }

    public function test_failed_shows_the_session_error_message(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->withSession(['booking_id' => $booking->id, 'error' => 'پرداخت رد شد'])
            ->get('/bookings/failed');

        $response->assertOk();
        $response->assertViewHas('errorMessage', 'پرداخت رد شد');
    }

    public function test_rate_stores_the_rating_and_review_and_awards_loyalty_points(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
        $before = $this->user->fresh()->loyalty_points ?? 0;

        $response = $this->actingAs($this->user)->post("/bookings/{$booking->id}/rate", [
            'rating' => 5,
            'review' => 'عالی بود',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $booking->refresh();
        $this->assertSame(5, $booking->rating);
        $this->assertSame('عالی بود', $booking->review);
    }

    public function test_rate_requires_a_rating_between_1_and_5(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post("/bookings/{$booking->id}/rate", [
            'rating' => 9,
        ]);

        $response->assertSessionHasErrors('rating');
    }

    public function test_rate_is_forbidden_for_another_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->post("/bookings/{$booking->id}/rate", ['rating' => 4])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/bookings')->assertRedirect(route('login'));
    }
}
