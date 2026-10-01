<?php

namespace Tests\Feature\Sms;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «نام کوتاه پیامکی» (تصمیم ۲۰۲۶-۰۹-۳۰): نام بلند سالن یا خدمت پیامک را از ۷۰ نویسه رد می‌کرد؛ سالن‌دار می‌تواند یک نام کوتاه
 * (حداکثر ۲۰ نویسه) فقط برای پیامک بگذارد. خالی = همان نام اصلی.
 */
class SmsShortNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sms_name_falls_back_to_the_full_name(): void
    {
        $salon = Salon::factory()->create(['name' => 'سالن زیبایی رز سفید', 'sms_name' => null]);
        $service = BeautyService::factory()->create(['name' => 'کراتینه و احیای موی آسیب‌دیده', 'sms_name' => 'کراتینه']);

        $this->assertSame('سالن زیبایی رز سفید', $salon->smsName());
        $this->assertSame('کراتینه', $service->smsName());
    }

    public function test_the_admin_sets_the_salons_sms_name_in_salon_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salon = app(CurrentSalon::class)->get();

        $this->actingAs($admin)->get(route('admin.salon-settings.edit'))->assertOk()->assertSee('name="sms_name"', false);
        $this->actingAs($admin)->put(route('admin.salon-settings.update'), ['name' => $salon->name, 'sms_name' => 'رز سفید'])->assertSessionHasNoErrors();

        $this->assertSame('رز سفید', $salon->fresh()->smsName());
    }

    public function test_the_admin_sets_a_services_sms_name_and_it_is_capped_at_twenty_characters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $service = BeautyService::factory()->create();
        $payload = ['name' => $service->name, 'price' => 100000, 'duration' => 60];

        $this->actingAs($admin)->get(route('admin.services.edit', $service))->assertOk()->assertSee('name="sms_name"', false);
        $this->actingAs($admin)->put(route('admin.services.update', $service), $payload + ['sms_name' => str_repeat('ک', 21)])->assertSessionHasErrors('sms_name');
        $this->actingAs($admin)->put(route('admin.services.update', $service), $payload + ['sms_name' => 'کراتینه'])->assertSessionHasNoErrors();

        $this->assertSame('کراتینه', $service->fresh()->smsName());
    }
}
