<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** پرونده‌ی مشتری در پنل مدیر (همراه بسته‌ی ب): فقط مشتری‌های همین سالن، فقط خواندنی */
class AdminCustomerFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_and_shows_only_this_salons_customers(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(\App\Support\CurrentSalon::class)->id(), 'name' => 'نیلوفر کاظمی', 'phone' => '09125556677']);
        Booking::factory()->create(['user_id' => $customer->id, 'status' => 'completed', 'booking_time' => now()->subDays(10)]);
        $other = Salon::factory()->create(['slug' => 'other-'.uniqid()]);
        $foreign = User::factory()->create(['user_type' => 'customer', 'salon_id' => $other->id, 'name' => 'مشتری سالن دیگر', 'phone' => '09125556678']);

        $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk()
            ->assertSee('نیلوفر کاظمی')->assertDontSee('مشتری سالن دیگر');
        $this->get(route('admin.customers.index', ['q' => '5556677']))->assertOk()->assertSee('نیلوفر کاظمی');
        $this->get(route('admin.customers.show', $customer->id))->assertOk()->assertSee('09125556677')->assertSee('آخرین نوبت‌ها');

        $this->get(route('admin.customers.show', $foreign->id))->assertNotFound();
        $this->get(route('admin.customers.show', $admin->id))->assertNotFound();
    }
}
