<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $user, array $overrides = []): Booking
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create();

        return Booking::factory()->create(array_merge([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'payment_status' => 'unpaid',
            'status' => 'pending_payment',
            'prepayment_amount' => 60000,
        ], $overrides));
    }

    // ── process() — full-discount path (regression guard for the documented white-screen bug) ──

    public function test_process_with_a_full_discount_marks_the_booking_paid_and_redirects_to_success(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, ['prepayment_amount' => 0, 'discount_code' => 'FULL100']);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/process");

        $response->assertRedirect(route('bookings.success', ['id' => $booking->id]));
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('full_discount', $booking->fresh()->payment_details['method']);
    }

    public function test_process_returns_the_response_object_not_null_on_the_full_discount_path(): void
    {
        // Regression guard for the historical white-screen bug: DB::transaction()'s closure must
        // explicitly return a Response, or a raw `null` leaks out and Laravel renders an empty
        // 200 page instead of a redirect.
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, ['prepayment_amount' => 0]);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/process");

        $this->assertNotNull($response);
        $response->assertRedirect();
    }

    public function test_process_on_an_already_paid_booking_short_circuits_to_the_result_page(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, ['payment_status' => 'paid']);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/process");

        $response->assertRedirect(route('payment.result'));
    }

    public function test_process_is_forbidden_for_a_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = $this->makeBooking($otherUser);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/process");

        $response->assertForbidden();
    }

    // ── process() — real gateway path (Http::fake) ──────────────────────

    public function test_process_redirects_to_the_gateway_url_on_a_successful_gateway_response(): void
    {
        Http::fake([
            '*request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTH123'],
            ], 200),
        ]);
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/process");

        $response->assertRedirect();
        $this->assertStringContainsString('AUTH123', $response->headers->get('Location'));
    }

    public function test_process_shows_an_error_when_the_gateway_rejects_the_request(): void
    {
        Http::fake([
            '*request.json' => Http::response(['errors' => ['message' => 'invalid merchant']], 200),
        ]);
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $response = $this->actingAs($user)->from("/payment/{$booking->id}")->post("/payment/{$booking->id}/process");

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    // ── processWithWallet() ──────────────────────────────────────────────

    public function test_full_wallet_payment_marks_the_booking_paid_without_touching_the_gateway(): void
    {
        Http::fake(); // any gateway call here would be a bug — fail loudly if one happens
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 100000]);
        $booking = $this->makeBooking($user, ['prepayment_amount' => 60000]);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/wallet", [
            'use_wallet' => true,
            'wallet_amount' => 60000,
        ]);

        $response->assertRedirect(route('bookings.success', ['id' => $booking->id]));
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(40000.0, (float) $wallet->fresh()->balance);
        Http::assertNothingSent();
    }

    public function test_wallet_payment_never_deducts_more_than_the_prepayment_amount(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 500000]); // far more than the booking needs
        $booking = $this->makeBooking($user, ['prepayment_amount' => 60000]);

        $this->actingAs($user)->post("/payment/{$booking->id}/wallet", [
            'use_wallet' => true,
            'wallet_amount' => 500000,
        ]);

        // Wallet must only be charged the prepayment amount (60,000), not the full requested/available amount.
        $this->assertSame(440000.0, (float) $wallet->fresh()->balance);
    }

    public function test_wallet_payment_never_deducts_more_than_the_wallet_balance(): void
    {
        Http::fake(['*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'AUTH1']], 200)]);
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 20000]);
        $booking = $this->makeBooking($user, ['prepayment_amount' => 60000]);

        $this->actingAs($user)->post("/payment/{$booking->id}/wallet", [
            'use_wallet' => true,
            'wallet_amount' => 20000,
        ]);

        // Wallet is fully drained (20,000), remaining 40,000 routed to the gateway.
        $this->assertSame(0.0, (float) $wallet->fresh()->balance);
    }

    public function test_partial_wallet_payment_refunds_the_wallet_if_the_gateway_call_fails(): void
    {
        Http::fake(['*request.json' => Http::response(['errors' => ['message' => 'gateway down']], 200)]);
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 20000]);
        $booking = $this->makeBooking($user, ['prepayment_amount' => 60000]);

        $this->actingAs($user)->from("/payment/{$booking->id}")->post("/payment/{$booking->id}/wallet", [
            'use_wallet' => true,
            'wallet_amount' => 20000,
        ]);

        // The wallet deduction must be rolled back (refunded) since the overall transaction
        // failed — the customer must not lose wallet money for a booking that never got paid.
        $this->assertSame(20000.0, (float) $wallet->fresh()->balance);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    public function test_wallet_payment_on_an_already_paid_booking_is_a_no_op(): void
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->update(['balance' => 100000]);
        $booking = $this->makeBooking($user, ['payment_status' => 'paid']);

        $response = $this->actingAs($user)->post("/payment/{$booking->id}/wallet", [
            'use_wallet' => true,
            'wallet_amount' => 60000,
        ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(100000.0, (float) $wallet->fresh()->balance);
    }
}
