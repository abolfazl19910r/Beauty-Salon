<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JSON صفحه‌های عمومی متخصص (بدون ورود) و فهرست متخصص‌های یک خدمت در فرم رزرو، شماره/ایمیل/درصد
 * کمیسیون متخصص را برمی‌گرداند (تصمیم ۲۰۲۶-۱۰-۱۱: حذف).
 */
class PublicSpecialistJsonPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->specialist = Specialist::factory()->create([
            'name' => 'سارا', 'phone' => '09125556677', 'email' => 'sara.private@example.com', 'commission_rate' => 37.5,
        ]);
        $this->service = BeautyService::factory()->create();
        $this->service->specialists()->attach($this->specialist->id);
    }

    private function assertNoContact(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertOk()
            ->assertDontSee('09125556677')
            ->assertDontSee('sara.private@example.com')
            ->assertDontSee('commission_rate')
            ->assertDontSee('user_id');
    }

    public function test_guest_json_of_the_public_specialist_pages_has_no_contact_data(): void
    {
        $this->assertNoContact($this->getJson('/s/rasta/specialists/search')->assertJsonPath('data.0.name', 'سارا'));
        $this->assertNoContact($this->getJson("/s/rasta/specialists/service/{$this->service->id}"));
        $this->assertNoContact($this->getJson("/s/rasta/specialists/{$this->specialist->id}")->assertJsonPath('specialist.name', 'سارا'));
        $this->assertNoContact($this->getJson('/s/rasta/specialists/top-rated'));
    }

    public function test_the_booking_form_specialist_list_has_no_contact_data(): void
    {
        $customer = User::factory()->create(['salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer']);

        $this->assertNoContact($this->actingAs($customer)->getJson(route('bookings.service-specialists', $this->service->id))
            ->assertJsonPath('0.name', 'سارا'));
    }
}
