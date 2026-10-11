<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\Holiday;
use App\Models\Salon;
use App\Models\Specialist;

/**
 * سالن، خدمات، متخصص‌ها و زمان‌های آزاد در اپ «ماهرو» — فقط داده‌ی سالن خود مشتری، متخصص بدون شماره/ایمیل.
 */
class CustomerCatalogApiTest extends CustomerApiTestCase
{
    public function test_salon_info_has_contact_and_booking_rules(): void
    {
        $this->salon->update(['address' => 'خیابان گل‌ها، پلاک ۷', 'phone' => '02144556677']);

        $this->api()->getJson('/api/v1/customer/salon')
            ->assertOk()
            ->assertJsonPath('data.slug', 'rasta')
            ->assertJsonPath('data.address', 'خیابان گل‌ها، پلاک ۷')
            ->assertJsonPath('data.rules.cancel_deadline_hours', 24)
            ->assertJsonPath('data.rules.payment_window_minutes', 30)
            ->assertJsonStructure(['data' => ['online_payment', 'working_hours', 'rules' => ['prepayment_percentage', 'cancellation_fee_percentage']]]);
    }

    public function test_services_and_specialists_are_only_this_salons_and_without_contact_data(): void
    {
        [$service, $specialist] = $this->bookable(null, ['name' => 'رنگ مو'], ['name' => 'سارا', 'phone' => '09125556677', 'email' => 'sara@example.com']);
        $other = Salon::factory()->create(['slug' => 'golbahar']);
        $this->bookable($other, ['name' => 'کراتین'], ['name' => 'نگار']);

        $this->api()->getJson('/api/v1/customer/services')
            ->assertOk()
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.name', 'رنگ مو')
            ->assertJsonPath('data.services.0.duration_minutes', 60)
            ->assertJsonStructure(['data' => ['services' => [['prepayment_amount', 'price']]]]);

        $this->api()->getJson('/api/v1/customer/specialists')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'سارا')
            ->assertDontSee('09125556677')->assertDontSee('sara@example.com');

        $this->api()->getJson("/api/v1/customer/services/{$service->id}/specialists")
            ->assertOk()->assertJsonPath('data.0.id', $specialist->id)->assertDontSee('09125556677');

        $this->api()->getJson("/api/v1/customer/specialists/{$specialist->id}")
            ->assertOk()->assertJsonPath('data.name', 'سارا')->assertJsonStructure(['data' => ['reviews', 'services']]);
    }

    public function test_another_salons_service_or_specialist_is_404(): void
    {
        $other = Salon::factory()->create(['slug' => 'golbahar']);
        [$service, $specialist] = $this->bookable($other);

        $this->api()->getJson("/api/v1/customer/services/{$service->id}/specialists")->assertNotFound();
        $this->api()->getJson("/api/v1/customer/specialists/{$specialist->id}")->assertNotFound();
        $this->api()->getJson("/api/v1/customer/specialists/{$specialist->id}/dates")->assertNotFound();
    }

    public function test_dates_skip_holidays_and_slots_use_the_service_duration(): void
    {
        [$service, $specialist] = $this->bookable();
        $tomorrow = now()->addDay()->toDateString();
        Holiday::create(['specialist_id' => $specialist->id, 'date' => now()->addDays(2)->toDateString(), 'description' => 'تعطیل']);

        $dates = $this->api()->getJson("/api/v1/customer/specialists/{$specialist->id}/dates?service_id={$service->id}")
            ->assertOk()->json('data');
        $this->assertContains($tomorrow, array_column($dates, 'date'));
        $this->assertNotContains(now()->addDays(2)->toDateString(), array_column($dates, 'date'));
        $this->assertCount(29, $dates, '۳۰ روز آینده منهای یک تعطیلی');

        $this->api()->getJson("/api/v1/customer/specialists/{$specialist->id}/slots?date={$tomorrow}&service_id={$service->id}")
            ->assertOk()
            ->assertJsonPath('data.0.time', '09:00')
            ->assertJsonPath('meta.duration_minutes', 60)
            ->assertJsonStructure(['data' => [['time', 'booking_time']], 'meta' => ['date_jalali']]);
    }

    public function test_a_specialist_who_does_not_offer_the_service_is_refused(): void
    {
        [$service] = $this->bookable();
        $stranger = Specialist::factory()->create();

        $this->api()->getJson("/api/v1/customer/specialists/{$stranger->id}/slots?date=".now()->addDay()->toDateString()."&service_id={$service->id}")
            ->assertStatus(422)->assertJsonPath('error.code', 'service_not_offered');
    }

    public function test_home_summarises_upcoming_bookings_points_and_wallet(): void
    {
        $this->bookingFor($this->customer);
        $this->customer->getOrCreateWallet()->update(['balance' => 15000]);

        $this->api()->getJson('/api/v1/customer/home')
            ->assertOk()
            ->assertJsonCount(1, 'data.upcoming_bookings')
            ->assertJsonPath('data.awaiting_payment_count', 1)
            ->assertJsonPath('data.wallet_balance', 15000)
            ->assertJsonPath('data.salon.slug', 'rasta');
    }

    public function test_a_customer_of_another_salon_sees_their_own_salon(): void
    {
        $other = Salon::factory()->create(['slug' => 'golbahar', 'name' => 'گلبهار']);
        $otherCustomer = $this->customerOf($other, '09121110000');
        $this->bookable($other, ['name' => 'کراتین']);
        $this->bookable(null, ['name' => 'رنگ مو']);

        $this->api($this->tokenFor($otherCustomer))->getJson('/api/v1/customer/services')
            ->assertOk()->assertJsonCount(1, 'data.services')->assertJsonPath('data.services.0.name', 'کراتین');
    }
}
