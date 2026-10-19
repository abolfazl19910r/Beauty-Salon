<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\Booking;
use App\Models\Salon;
use App\Services\Salon\SalonPauseService;
use Illuminate\Support\Facades\Notification;

/**
 * بسته‌ی «ح»، ۸: اپ مشتری وضعیت توقف را در کارت سالن می‌بیند و رزرو تازه ۴۲۳ salon_paused می‌گیرد.
 */
class CustomerSalonPauseApiTest extends CustomerApiTestCase
{
    public function test_salon_card_shows_the_pause_and_booking_is_refused(): void
    {
        Notification::fake();
        [$service, $specialist] = $this->bookable();
        $this->api()->getJson('/api/v1/customer/salon')->assertOk()->assertJsonPath('data.booking_paused', null);

        app(SalonPauseService::class)->pause($this->salon, $this->customer, Salon::PAUSED_BY_OWNER, 'تعمیرات', now()->addDays(2));

        $this->api()->getJson('/api/v1/customer/salon')->assertOk()
            ->assertJsonPath('data.booking_paused.reason', 'تعمیرات')
            ->assertJsonStructure(['data' => ['booking_paused' => ['message', 'reason', 'until']]]);

        $this->api()->postJson('/api/v1/customer/bookings', [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => now()->addDay()->setTime(10, 0)->toIso8601String(),
        ])->assertStatus(423)->assertJsonPath('error.code', 'salon_paused');
        $this->assertSame(0, Booking::count());
    }
}
