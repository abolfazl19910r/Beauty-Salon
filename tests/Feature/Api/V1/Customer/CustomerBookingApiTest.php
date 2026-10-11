<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * نوبت‌های مشتری در اپ «ماهرو»: رزرو، فهرست، لغو، جابه‌جایی، امتیاز — همان قاعده‌های وب.
 */
class CustomerBookingApiTest extends CustomerApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function slotTomorrow(string $time = '10:00'): string
    {
        return now()->addDay()->setTimeFromTimeString($time)->toIso8601String();
    }

    public function test_quote_shows_prepayment_and_the_discount_taken_off_the_remaining(): void
    {
        [$service] = $this->bookable();
        DiscountCode::create(['code' => 'NOROOZ', 'type' => 'fixed', 'amount' => 20000, 'max_uses' => 10, 'used_count' => 0, 'is_active' => true]);

        $this->api()->postJson('/api/v1/customer/bookings/quote', ['service_id' => $service->id, 'discount_code' => 'NOROOZ'])
            ->assertOk()
            ->assertJsonPath('data.price', 200000)
            ->assertJsonPath('data.discount_code', 'NOROOZ')
            ->assertJsonPath('data.discount_amount', 20000)
            ->assertJsonStructure(['data' => ['prepayment_amount', 'remaining_amount', 'gateways', 'wallet_balance', 'online_payment']]);

        $this->api()->postJson('/api/v1/customer/bookings/quote', ['service_id' => $service->id, 'discount_code' => 'NADARIM'])
            ->assertStatus(422)->assertJsonPath('error.code', 'discount_code_invalid');
    }

    public function test_another_customers_personal_code_is_refused(): void
    {
        [$service, $specialist] = $this->bookable();
        $other = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]);
        DiscountCode::create(['code' => 'PERSONAL1', 'type' => 'fixed', 'amount' => 20000, 'max_uses' => 1, 'used_count' => 0, 'is_active' => true, 'user_id' => $other->id]);

        $this->api()->postJson('/api/v1/customer/bookings', [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->slotTomorrow(), 'discount_code' => 'PERSONAL1',
        ])->assertStatus(422)->assertJsonPath('error.code', 'discount_code_invalid');

        $this->assertSame(0, Booking::count());
    }

    public function test_booking_creates_a_pending_payment_booking_and_returns_payment_info(): void
    {
        [$service, $specialist] = $this->bookable();
        $key = (string) Str::uuid();
        $payload = ['service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->slotTomorrow(), 'idempotency_key' => $key];

        $first = $this->api()->postJson('/api/v1/customer/bookings', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.actions.pay', true)
            ->assertJsonPath('data.specialist.id', $specialist->id)
            ->assertJsonPath('meta.payment.required', true)
            ->assertJsonStructure(['data' => ['pay_before', 'booking_time_jalali'], 'meta' => ['payment' => ['amount', 'gateways']]]);

        $booking = Booking::sole();
        $this->assertSame($this->customer->id, $booking->user_id);
        $this->assertSame(now()->addDay()->format('Y-m-d').' 10:00:00', $booking->booking_time->format('Y-m-d H:i:s'));

        // دوباره همان درخواست (شبکه‌ی ضعیف) → همان نوبت
        $this->api()->postJson('/api/v1/customer/bookings', $payload)->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, Booking::count());
    }

    public function test_a_taken_slot_or_a_wrong_specialist_is_refused(): void
    {
        [$service, $specialist] = $this->bookable();
        [, $otherSpecialist] = $this->bookable();
        $this->bookingFor(User::factory()->create(), ['specialist_id' => $specialist->id, 'service_id' => $service->id,
            'booking_time' => now()->addDay()->setTime(10, 0), 'status' => 'confirmed']);

        $this->api()->postJson('/api/v1/customer/bookings', ['service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->slotTomorrow()])
            ->assertStatus(409)->assertJsonPath('error.code', 'booking_not_available');
        $this->api()->postJson('/api/v1/customer/bookings', ['service_id' => $service->id, 'specialist_id' => $otherSpecialist->id, 'booking_time' => $this->slotTomorrow('11:00')])
            ->assertStatus(422)->assertJsonPath('error.code', 'service_not_offered');
    }

    public function test_a_salon_without_a_gateway_does_not_take_prepaid_bookings(): void
    {
        [$service, $specialist] = $this->bookable();
        $this->salon->paymentGateways()->delete();
        $this->salon->update(['zarinpal_merchant_id' => null]);

        $this->api()->postJson('/api/v1/customer/bookings', ['service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->slotTomorrow()])
            ->assertStatus(422)->assertJsonPath('error.code', 'online_payment_unavailable');
    }

    public function test_index_splits_upcoming_and_past_and_shows_only_own_bookings(): void
    {
        $upcoming = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid']);
        $done = $this->bookingFor($this->customer, ['status' => 'completed', 'payment_status' => 'paid', 'booking_time' => now()->subDays(2)]);
        $cancelled = $this->bookingFor($this->customer, ['status' => 'cancelled', 'booking_time' => now()->addDays(5)]);
        $this->bookingFor(User::factory()->create());

        $this->api()->getJson('/api/v1/customer/bookings')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $upcoming->id)->assertJsonPath('meta.scope', 'upcoming');
        $past = $this->api()->getJson('/api/v1/customer/bookings?scope=past')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$done->id, $cancelled->id], array_column($past, 'id'));
    }

    public function test_another_customers_booking_is_404(): void
    {
        $theirs = $this->bookingFor(User::factory()->create());

        $this->api()->getJson("/api/v1/customer/bookings/{$theirs->id}")->assertNotFound();
        $this->api()->postJson("/api/v1/customer/bookings/{$theirs->id}/cancel")->assertNotFound();
        $this->api()->postJson("/api/v1/customer/bookings/{$theirs->id}/pay")->assertNotFound();
    }

    public function test_a_booking_of_the_same_phone_in_another_salon_is_not_visible(): void
    {
        $other = Salon::factory()->create(['slug' => 'golbahar']);
        $twin = $this->customerOf($other, '09121110000');
        $booking = $this->bookingFor($this->customer);

        $this->api($this->tokenFor($twin))->getJson("/api/v1/customer/bookings/{$booking->id}")->assertNotFound();
    }

    public function test_cancel_follows_the_24_hour_rule(): void
    {
        $far = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid', 'booking_time' => now()->addDays(3)]);
        $near = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid', 'booking_time' => now()->addHours(5)]);

        $this->api()->postJson("/api/v1/customer/bookings/{$near->id}/cancel")
            ->assertStatus(409)->assertJsonPath('error.code', 'booking_not_cancellable');
        $this->api()->postJson("/api/v1/customer/bookings/{$far->id}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('meta.changed', true);
        $this->api()->postJson("/api/v1/customer/bookings/{$far->id}/cancel")->assertOk()->assertJsonPath('meta.changed', false);

        $this->assertSame('customer', $far->fresh()->cancelled_by);
    }

    public function test_reschedule_moves_the_booking_to_a_free_slot(): void
    {
        $booking = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid', 'booking_time' => now()->addDays(3)->setTime(10, 0)]);
        $target = now()->addDays(4)->setTime(14, 0);

        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/reschedule", ['booking_time' => $target->toIso8601String()])
            ->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertSame($target->format('Y-m-d H:i'), $booking->fresh()->booking_time->format('Y-m-d H:i'));

        $this->api()->postJson("/api/v1/customer/bookings/{$booking->id}/reschedule", ['booking_time' => now()->addDays(4)->setTime(22, 0)->toIso8601String()])
            ->assertStatus(409)->assertJsonPath('error.code', 'booking_not_available');
    }

    public function test_rate_only_once_and_only_completed(): void
    {
        $done = $this->bookingFor($this->customer, ['status' => 'completed', 'payment_status' => 'paid', 'booking_time' => now()->subDay()]);
        $upcoming = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid']);

        $this->api()->postJson("/api/v1/customer/bookings/{$done->id}/rate", ['rating' => 5, 'review' => 'عالی'])
            ->assertOk()->assertJsonPath('data.rating', 5)->assertJsonPath('data.actions.rate', false);
        $this->api()->postJson("/api/v1/customer/bookings/{$done->id}/rate", ['rating' => 1])
            ->assertStatus(409)->assertJsonPath('error.code', 'booking_action_not_allowed');
        $this->api()->postJson("/api/v1/customer/bookings/{$upcoming->id}/rate", ['rating' => 5])->assertStatus(409);

        $this->assertSame(10, $this->customer->fresh()->getTotalLoyaltyPoints());
    }
}
