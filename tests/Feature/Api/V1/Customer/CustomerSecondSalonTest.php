<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\Booking;
use App\Models\Salon;

/**
 * سالن دوم (غیر rasta) در اپ «ماهرو»: همه‌چیز از سالن خود توکن، و تعلیق همان سالن (سند، بسته‌ی ۳).
 */
class CustomerSecondSalonTest extends CustomerApiTestCase
{
    private Salon $golbahar;

    private string $golbaharToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->golbahar = Salon::factory()->create(['slug' => 'golbahar', 'name' => 'گلبهار']);
        $this->golbaharToken = $this->tokenFor($this->customerOf($this->golbahar, '09121110000'));
    }

    public function test_a_second_salon_customer_books_in_their_own_salon(): void
    {
        [$service, $specialist] = $this->bookable($this->golbahar, ['name' => 'کراتین']);

        $this->api($this->golbaharToken)->getJson('/api/v1/customer/salon')->assertOk()->assertJsonPath('data.slug', 'golbahar');
        $this->api($this->golbaharToken)->postJson('/api/v1/customer/bookings', [
            'service_id' => $service->id, 'specialist_id' => $specialist->id,
            'booking_time' => now()->addDay()->setTime(11, 0)->toIso8601String(),
        ])->assertCreated();

        $booking = Booking::withoutGlobalScopes()->sole();
        $this->assertSame($this->golbahar->id, $booking->salon_id);

        // همان شماره در سالن rasta حساب جداست و این نوبت را نمی‌بیند
        $this->api()->getJson("/api/v1/customer/bookings/{$booking->id}")->assertNotFound();
        $this->api()->getJson('/api/v1/customer/bookings')->assertJsonCount(0, 'data');
    }

    public function test_suspending_the_salon_blocks_its_customers_without_deleting_their_tokens(): void
    {
        $this->golbahar->update(['is_suspended' => true]);

        $this->api($this->golbaharToken)->getJson('/api/v1/customer/home')
            ->assertForbidden()->assertJsonPath('error.code', 'salon_inactive');
        $this->getJson('/api/v1/customer/salons/golbahar')->assertNotFound();
        $this->postJson('/api/v1/customer/salons/golbahar/login', ['phone' => '09121110000', 'password' => 'password'])->assertNotFound();
        $this->api()->getJson('/api/v1/customer/home')->assertOk();

        $this->golbahar->update(['is_suspended' => false]);
        $this->api($this->golbaharToken)->getJson('/api/v1/customer/home')->assertOk()->assertJsonPath('data.salon.slug', 'golbahar');
    }
}
