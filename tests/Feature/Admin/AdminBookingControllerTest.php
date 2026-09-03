<?php

namespace Tests\Feature\Admin;

use App\Models\AdminWallet;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminBookingController had no dedicated HTTP-level test until this session — the admin's
 * ability to create/edit/cancel/delete bookings directly is a financially sensitive path
 * (AdminBookingService::buildUpdatePayload() sets cancelled_by='admin' + dispatches
 * BookingCancelled, which BookingObserver uses to trigger the wallet-credit refund path —
 * see the R-Observers/"رفع مستقل: برگشت وجه" history in Rasta_unified_prompt.md).
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
        $response->assertViewHas('users');
        $response->assertViewHas('services');
        $response->assertViewHas('specialists');
    }

    public function test_store_creates_a_booking(): void
    {
        $user = User::factory()->create();
        $service = BeautyService::factory()->create();
        $specialist = Specialist::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/bookings', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('admin.bookings.show', $booking));
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
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
        $booking = Booking::factory()->create(['status' => 'pending']);
        $newUser = User::factory()->create();
        $newService = BeautyService::factory()->create();
        $newSpecialist = Specialist::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", [
            'user_id' => $newUser->id,
            'service_id' => $newService->id,
            'specialist_id' => $newSpecialist->id,
            'booking_time' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'notes' => 'یادداشت جدید',
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking));
        $booking->refresh();
        $this->assertSame($newUser->id, $booking->user_id);
        $this->assertSame('یادداشت جدید', $booking->notes);
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
