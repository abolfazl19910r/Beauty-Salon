<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Specialist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ⭐ صفحه‌ی عمومی پروفایل متخصص (۲۰۲۶-۰۹-۲۴) — قبلاً view 'specialists.show' وجود نداشت و ۵۰۰ می‌داد.
 */
class PublicSpecialistProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_with_photo_services_and_without_personal_phone(): void
    {
        $specialist = Specialist::factory()->create(['name' => 'نگار احمدی', 'phone' => '09125554433', 'photo_path' => 'salons/1/specialists/n.jpg']);
        $service = BeautyService::factory()->create(['name' => 'رنگ و لایت']);
        $specialist->services()->attach($service->id);

        $this->get("/s/rasta/specialists/{$specialist->id}")
            ->assertOk()
            ->assertSee('نگار احمدی')
            ->assertSee('رنگ و لایت')
            ->assertSee('salons/1/specialists/n.jpg', false)
            ->assertDontSee('09125554433');
    }

    public function test_home_cards_link_to_the_profile(): void
    {
        $specialist = Specialist::factory()->create();
        Cache::flush();

        $this->get('/s/rasta')->assertOk()->assertSee(route('specialists.show', $specialist), false);
    }

    public function test_another_salons_specialist_is_not_found(): void
    {
        $other = \App\Models\Salon::factory()->create();
        app(\App\Support\CurrentSalon::class)->set($other);
        $foreign = Specialist::factory()->create();
        app(\App\Support\CurrentSalon::class)->set(\App\Models\Salon::where('slug', 'rasta')->first());

        $this->get("/s/rasta/specialists/{$foreign->id}")->assertNotFound();
    }
}
