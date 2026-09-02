<?php

namespace Tests\Feature\Admin;

use App\Models\AdminWallet;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Booking\BookingRescheduledNotification;
use Tests\TestCase;

/**
 * AdminBookingController had no dedicated HTTP-level test until this session — the admin's
 * ability to create/edit/cancel/delete bookings directly is a financially sensitive path
 * (AdminBookingService::buildUpdatePayload() sets cancelled_by='admin' + dispatches
 * BookingCancelled, which BookingObserver uses to trigger the wallet-credit refund path —
 * see the R-Observers/"رفع مستقل: برگشت وجه" history in Rasta_unified_prompt.md).
 *
 * ⭐ Fix (fix/admin-booking-slot-conflict, commits 2-4): several tests below were updated
 * rather than left as-is, because the bug this branch fixes is EXACTLY the thing those tests
 * were unknowingly relying on — "any specialist, any time, always succeeds" was only true
 * because AdminBookingController::store()/AdminBookingService::updateFull() had zero
 * availability check. Once that check exists, a specialist with no working schedule (the
 * factory default) correctly has zero available slots, so tests that create/move a booking to
 * such a specialist now need makeFullyAvailableSpecialist() same as BookingServiceTest already
 * uses for the online flow.
 */
class AdminBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /**
     * Same helper BookingServiceTest uses for the online flow — a specialist open every day,
     * 08:00-20:00, so any future daytime slot is available.
     */
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

    public function test_index_lists_bookings_and_computes_stats(): void
    {
        Booking::factory()->count(2)->create(['status' => 'confirmed']);
        Booking::factory()->create(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin)->get('/admin/bookings');

        $response->assertOk();
        $response->assertViewHas('totalBookings', 3);
        $response->assertViewHas('confirmedBookings', 2);
        $response->assertViewHas('cancelledBookings', 1);
    }

    public function test_index_filters_by_status(): void
    {
        Booking::factory()->create(['status' => 'pending']);
        Booking::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)->get('/admin/bookings?status=pending');

        $bookings = $response->viewData('bookings');
        $this->assertCount(1, $bookings);
        $this->assertSame('pending', $bookings->first()->status);
    }

    public function test_index_filters_by_date(): void
    {
        $today = Booking::factory()->create(['booking_time' => now()->addDay()->setTime(10, 0)]);
        Booking::factory()->create(['booking_time' => now()->addDays(5)->setTime(10, 0)]);

        $response = $this->actingAs($this->admin)->get('/admin/bookings?date='.now()->addDay()->format('Y-m-d'));

        $bookings = $response->viewData('bookings');
        $this->assertCount(1, $bookings);
        $this->assertSame($today->id, $bookings->first()->id);
    }

    public function test_create_renders_the_form_with_reference_data(): void
    {
        BeautyService::factory()->create();
        Specialist::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/bookings/create');

        $response->assertOk();
        // ⭐ Fix (commit 3): 'users' is deliberately no longer passed to this view — the
        // customer search/quick-create widget (AdminBookingCustomerController) replaced the
        // old User::all() dropdown, so there is nothing named 'users' to assert here anymore.
        $response->assertViewHas('services');
        $response->assertViewHas('specialists');
    }

    public function test_store_creates_a_booking(): void
    {
        $user = User::factory()->create();
        $service = BeautyService::factory()->create();
        $specialist = $this->makeFullyAvailableSpecialist();

        $response = $this->actingAs($this->admin)->post('/admin/bookings', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'phone',
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('admin.bookings.show', $booking));
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'status' => 'pending',
            'source' => 'phone',
        ]);
    }

    public function test_store_rejects_a_slot_already_taken_online(): void
    {
        $service = BeautyService::factory()->create();
        $specialist = $this->makeFullyAvailableSpecialist();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        // Simulates an existing online booking already occupying this exact slot.
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'confirmed',
            'source' => 'online',
        ]);

        $walkInUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/bookings', [
            'user_id' => $walkInUser->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => $bookingTime,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'walk_in',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', now()->addDays(2)->format('Y-m-d'))
            ->count());
    }

    public function test_store_allows_reusing_a_slot_freed_by_a_cancelled_booking(): void
    {
        $service = BeautyService::factory()->create();
        $specialist = $this->makeFullyAvailableSpecialist();
        $bookingTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'cancelled',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/bookings', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => $bookingTime,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'phone',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertSame(2, Booking::where('specialist_id', $specialist->id)->count());
    }

    public function test_show_renders_the_booking_with_relations_loaded(): void
    {
        $booking = Booking::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/bookings/{$booking->id}");

        $response->assertOk();
        $response->assertViewHas('booking', function ($viewBooking) {
            return $viewBooking->relationLoaded('service')
                && $viewBooking->relationLoaded('user')
                && $viewBooking->relationLoaded('specialist');
        });
    }

    public function test_edit_renders_the_form(): void
    {
        $booking = Booking::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/bookings/{$booking->id}/edit");

        $response->assertOk();
        $response->assertViewHas('booking');
    }

    public function test_status_only_update_confirms_a_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_status_only_update_to_cancelled_sets_cancelled_by_admin_and_triggers_refund(): void
    {
        $service = BeautyService::factory()->create(['price' => 250000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $user = User::factory()->create();

        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'prepayment_amount' => 75000,
        ]);
        // Trigger the paid-transition observer path exactly like a real payment would,
        // so the specialist actually has an income transaction to reverse.
        $booking->update(['payment_status' => 'unpaid']);
        $booking->update(['payment_status' => 'paid']);

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $booking->refresh();

        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('admin', $booking->cancelled_by);
        $this->assertNotNull($booking->cancelled_at);
        // Admin cancellation refunds in full, with no penalty (per BookingObserver docs).
        $this->assertSame(75000.0, (float) $booking->refunded_amount);
        $this->assertSame(75000.0, (float) $user->wallet->fresh()->balance);
        $this->assertSame(0.0, (float) AdminWallet::getWallet()->fresh()->balance);
    }

    public function test_full_update_edits_all_booking_fields(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        $newUser = User::factory()->create();
        $newService = BeautyService::factory()->create();
        $newSpecialist = $this->makeFullyAvailableSpecialist();

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $newUser->id,
            'service_id' => $newService->id,
            'specialist_id' => $newSpecialist->id,
            'booking_time' => now()->addDays(3)->setTime(11, 0)->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'notes' => 'یادداشت جدید',
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking));
        $booking->refresh();
        $this->assertSame($newUser->id, $booking->user_id);
        $this->assertSame('یادداشت جدید', $booking->notes);
    }

    public function test_full_update_keeping_the_same_time_does_not_self_collide(): void
    {
        // ⭐ Fix (commit 4): the exact edge case Specialist::getAvailableSlots()'s new
        // $excludeBookingId parameter exists for — a booking must never be treated as
        // colliding with its own current slot when nothing about the schedule changes.
        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $bookingTime = now()->addDays(2)->setTime(10, 0);

        $booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'booking_time' => $bookingTime,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $booking->user_id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => $bookingTime->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'notes' => 'همون ساعت، فقط وضعیت عوض شد',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_full_update_rejects_moving_into_an_already_taken_slot(): void
    {
        $specialistA = $this->makeFullyAvailableSpecialist();
        $specialistB = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $contestedTime = now()->addDays(2)->setTime(14, 0)->format('Y-m-d H:i:s');

        Booking::factory()->create([
            'specialist_id' => $specialistB->id,
            'service_id' => $service->id,
            'booking_time' => $contestedTime,
            'status' => 'confirmed',
        ]);

        $bookingToMove = Booking::factory()->create([
            'specialist_id' => $specialistA->id,
            'service_id' => $service->id,
            'booking_time' => now()->addDays(2)->setTime(9, 0),
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$bookingToMove->id}", [
            'user_id' => $bookingToMove->user_id,
            'service_id' => $service->id,
            'specialist_id' => $specialistB->id,
            'booking_time' => $contestedTime,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(9, $bookingToMove->fresh()->booking_time->hour);
    }

    public function test_full_update_changing_schedule_notifies_the_customer(): void
    {
        Notification::fake();

        $specialist = $this->makeFullyAvailableSpecialist();
        $service = BeautyService::factory()->create();
        $user = User::factory()->create();

        $booking = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
            'booking_time' => now()->addDays(2)->setTime(9, 0),
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(2)->setTime(16, 0)->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        // ⭐ Fix (commit 4): notified to the CUSTOMER (the booking's own user), not the
        // specialist — matching what NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER's name
        // and gate label ("تغییر زمان نوبت — اطلاع به مشتری") actually say this event is for.
        Notification::assertSentTo($user, BookingRescheduledNotification::class);
    }

    public function test_full_update_without_schedule_change_does_not_notify(): void
    {
        Notification::fake();

        $booking = Booking::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);

        $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $booking->user_id,
            'service_id' => $booking->service_id,
            'specialist_id' => $booking->specialist_id,
            'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        Notification::assertNothingSent();
    }

    public function test_full_update_refuses_to_change_customer_on_a_paid_booking(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'paid']);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $otherUser->id,
            'service_id' => $booking->service_id,
            'specialist_id' => $booking->specialist_id,
            'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
            'status' => $booking->status,
            'payment_status' => 'paid',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertSame($booking->user_id, $booking->fresh()->user_id);
    }

    public function test_full_update_allows_changing_customer_on_an_unpaid_booking(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'unpaid']);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $otherUser->id,
            'service_id' => $booking->service_id,
            'specialist_id' => $booking->specialist_id,
            'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
            'status' => $booking->status,
            'payment_status' => 'unpaid',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($otherUser->id, $booking->fresh()->user_id);
    }

    public function test_destroy_deletes_an_unpaid_booking(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'unpaid']);

        $response = $this->actingAs($this->admin)->delete("/admin/bookings/{$booking->id}");

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_destroy_refuses_to_delete_a_paid_booking(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'paid']);

        $response = $this->actingAs($this->admin)->delete("/admin/bookings/{$booking->id}");

        $response->assertRedirect(route('admin.bookings.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $booking = Booking::factory()->create();

        $this->actingAs($user)->get('/admin/bookings')->assertForbidden();
        $this->actingAs($user)->get("/admin/bookings/{$booking->id}")->assertForbidden();
    }
}

