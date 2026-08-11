<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User
    {
        return User::factory()->create(['two_factor_enabled' => true]);
    }

    // ── initiate() ───────────────────────────────────────────────────────

    public function test_initiate_creates_a_pending_payment_and_returns_the_verify_url(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'prepayment_amount' => 75000, 'payment_status' => 'unpaid']);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->postJson(route('api.payments.secure.initiate', $booking));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 75000,
            'status' => 'pending',
        ]);
    }

    public function test_initiate_is_forbidden_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)
            ->withSession(['2fa_verified' => true])
            ->postJson(route('api.payments.secure.initiate', $booking));

        $response->assertForbidden();
    }

    public function test_initiate_rejects_an_already_paid_booking(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'payment_status' => 'paid']);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->postJson(route('api.payments.secure.initiate', $booking));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id]);
    }

    // ── verify() ─────────────────────────────────────────────────────────

    public function test_verify_marks_booking_and_payment_paid_and_sets_payment_details_on_the_booking(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'prepayment_amount' => 60000, 'payment_status' => 'unpaid']);

        // Real flow through the service so card_data carries a valid tamper-evidence signature.
        $service = app(\App\Services\SecurePaymentService::class);
        $payment = $service->createPayment($booking);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('payments.secure.verify.submit', $payment->reference_id));

        $response->assertRedirect(route('payments.secure.result', [
            'reference' => $payment->reference_id,
            'status' => 'success',
        ]));

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->payment_reference);
        $this->assertSame('gateway', $booking->payment_details['method']);
        $this->assertTrue($booking->payment_details['secure_payment']);

        $payment->refresh();
        $this->assertSame('completed', $payment->status);
    }

    public function test_verify_fails_gracefully_for_an_expired_payment_without_touching_the_booking(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'expired_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('payments.secure.verify.submit', $payment->reference_id));

        $response->assertRedirect(route('payments.secure.result', [
            'reference' => $payment->reference_id,
            'status' => 'failed',
            'message' => 'مهلت پرداخت به پایان رسیده است. لطفاً دوباره تلاش کنید.',
        ]));

        $booking->refresh();
        $this->assertSame('unpaid', $booking->payment_status);

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
    }

    public function test_verify_is_idempotent_for_an_already_completed_payment(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'payment_status' => 'paid']);
        $payment = Payment::factory()->completed()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->post(route('payments.secure.verify.submit', $payment->reference_id));

        $response->assertRedirect(route('payments.secure.result', [
            'reference' => $payment->reference_id,
            'status' => 'success',
        ]));
    }

    // ── checkStatus() ───────────────────────────────────────────────────

    public function test_check_status_returns_the_payment_state(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'status' => 'pending']);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->getJson(route('api.payments.secure.status', $payment->reference_id));

        $response->assertOk()->assertJsonStructure([
            'status', 'paid_at', 'gateway_reference', 'remaining_seconds', 'booking_status',
        ]);
    }

    public function test_check_status_is_forbidden_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs($stranger)
            ->withSession(['2fa_verified' => true])
            ->getJson(route('api.payments.secure.status', $payment->reference_id));

        $response->assertForbidden();
    }

    // ── showCheckout() ──────────────────────────────────────────────────

    public function test_show_checkout_redirects_away_for_an_already_paid_booking(): void
    {
        $user = $this->verifiedUser();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'payment_status' => 'paid']);

        $response = $this->actingAs($user)
            ->withSession(['2fa_verified' => true])
            ->get(route('payments.secure.checkout', $booking));

        $response->assertRedirect(route('bookings.show', $booking));
    }
}
