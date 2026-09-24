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

        $response = $this->actingAs($user)->post(route('payment.process', $booking));

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

        $response = $this->actingAs($user)->post(route('payment.process', $booking));

        $this->assertNotNull($response);
        $response->assertRedirect();
    }

    public function test_process_on_an_already_paid_booking_short_circuits_to_the_result_page(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, ['payment_status' => 'paid']);

        $response = $this->actingAs($user)->post(route('payment.process', $booking));

        $response->assertRedirect(route('payment.result'));
    }

    public function test_process_is_forbidden_for_a_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = $this->makeBooking($otherUser);

        $response = $this->actingAs($user)->post(route('payment.process', $booking));

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

        $response = $this->actingAs($user)->post(route('payment.process', $booking));

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

        $response = $this->actingAs($user)->from(route('payment.show', $booking))->post(route('payment.process', $booking));

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
    }

    // ── مورد ۹ فاز ۲ («مرچنت آیدی مجزا برای هر سالن») ──────────────────────

    public function test_process_uses_the_salons_own_merchant_id_when_set(): void
    {
        $salon = app(\App\Support\CurrentSalon::class)->get();
        $salon->update(['zarinpal_merchant_id' => 'salon-specific-merchant']);

        Http::fake([
            '*request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTH_SALON'],
            ], 200),
        ]);

        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $this->actingAs($user)->post(route('payment.process', $booking));

        Http::assertSent(fn ($request) => $request['merchant_id'] === 'salon-specific-merchant');
    }

    /**
     * ⭐ جایگزین تست قبلی «fallback به مرچنت سراسری» (تصمیم ابوالفضل، ۲۰۲۶-۰۹-۲۴): سالنی که کد
     * پذیرنده‌ی خودش رو نداره هیچ پرداخت آنلاینی نداره — قبلاً پول مشتری‌های سالن بی‌صدا به
     * مرچنت پلتفرم (ZARINPAL_MERCHANT_ID) واریز می‌شد.
     */
    public function test_process_never_falls_back_to_the_platform_merchant_when_the_salon_has_none(): void
    {
        $salon = app(\App\Support\CurrentSalon::class)->get();
        $salon->update(['zarinpal_merchant_id' => null]);

        Http::fake();

        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $response = $this->actingAs($user)->from(route('payment.show', $booking))->post(route('payment.process', $booking));

        Http::assertNothingSent();
        $response->assertSessionHas('error', fn ($msg) => str_contains($msg, 'پرداخت آنلاین این سالن هنوز فعال نشده است'));
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

        $response = $this->actingAs($user)->post(route('payment.wallet', $booking), [
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

        $this->actingAs($user)->post(route('payment.wallet', $booking), [
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

        $this->actingAs($user)->post(route('payment.wallet', $booking), [
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

        $this->actingAs($user)->from(route('payment.show', $booking))->post(route('payment.wallet', $booking), [
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

        $response = $this->actingAs($user)->post(route('payment.wallet', $booking), [
            'use_wallet' => true,
            'wallet_amount' => 60000,
        ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(100000.0, (float) $wallet->fresh()->balance);
    }

    /**
     * ⭐ مرحله‌ی ۰ بخش ۲ (۲۰۲۶-۰۹-۲۵) — زنجیره‌ی کامل: شروع پرداخت → درگاه به آدرس بازگشت مشترک
     * برمی‌گرده → 303 به callback کسب‌وکار (با session سالم) → تأیید با مبلغ ثبت‌شده → نوبت پرداخت‌شده.
     */
    public function test_end_to_end_through_the_shared_return_url_marks_the_booking_paid(): void
    {
        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A-E2E']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'REF-E2E']], 200),
        ]);
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $this->actingAs($user)->post(route('payment.process', $booking));
        $tx = \App\Models\PaymentTransaction::where('payable_id', $booking->id)->where('purpose', 'booking')->sole();
        $this->assertStringContainsString('booking='.$booking->id, $tx->callback_url);

        $bounce = $this->get("/payments/return/{$tx->public_id}?Authority=A-E2E&Status=OK");
        $bounce->assertStatus(303);

        $this->actingAs($user)->get($bounce->headers->get('Location'));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('paid', $tx->fresh()->status);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'verify.json') && $r['amount'] === 600000);
    }
}
