<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminPaymentController (manual/offline payment recording by an admin) had no dedicated
 * HTTP test — this is also the exact controller where the documented "payment_method isn't
 * a real column, R-Observers fix wasn't actually applied" bug lived; the regression guard
 * here asserts the fixed behavior (payment_details->method + admin_recorded flag).
 */
class AdminPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_create_renders_the_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/payments/create');

        $response->assertOk();
    }

    public function test_create_preloads_a_booking_when_given(): void
    {
        $booking = Booking::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/payments/create?booking_id={$booking->id}");

        $response->assertOk();
        $response->assertViewHas('booking', function ($viewBooking) use ($booking) {
            return $viewBooking->id === $booking->id;
        });
    }

    public function test_store_records_a_manual_payment_under_payment_details(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'payment_status' => 'unpaid',
            'prepayment_amount' => 60000,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/payments', [
            'booking_id' => $booking->id,
            'amount' => 60000,
            'payment_method' => 'cash',
            'reference' => 'CASH-001',
            'notes' => 'پرداخت نقدی حضوری',
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking));
        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('cash', $booking->payment_details['method']);
        $this->assertTrue($booking->payment_details['admin_recorded']);
        $this->assertSame('CASH-001', $booking->payment_reference);
        $this->assertNotNull($booking->paid_at);
    }

    public function test_store_credits_the_specialist_wallet_via_the_normal_observer_path(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create(['commission_rate' => 10]);
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'payment_status' => 'unpaid',
            'prepayment_amount' => 60000,
        ]);

        $this->actingAs($this->admin)->post('/admin/payments', [
            'booking_id' => $booking->id,
            'amount' => 60000,
            'payment_method' => 'card',
        ]);

        // 10% commission on the 60,000 prepayment => specialist keeps 54,000, pending settlement.
        $this->assertSame(54000.0, (float) $specialist->wallet->fresh()->pending_amount);
    }

    public function test_store_requires_a_valid_booking(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/payments', [
            'booking_id' => 99999,
            'amount' => 10000,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('booking_id');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/payments/create')->assertForbidden();
    }
}
