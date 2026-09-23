<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ صفحه‌های عمومی متخصص‌ها (۲۰۲۶-۰۹-۲۴): جستجو، برترین‌ها، متخصص‌های یک خدمت و تقویم نوبت‌های
 * خالی. همه‌شون قبلاً به viewهایی ارجاع می‌دادن که وجود نداشت (۵۰۰)، و جستجو/برترین‌ها به‌خاطر
 * ثبت تکراری در routes/web/services.php بی‌صدا پشت لاگین بودن.
 */
class PublicSpecialistPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_is_public_and_filters_by_name_and_service(): void
    {
        $service = BeautyService::factory()->create(['name' => 'کاشت ناخن']);
        $a = Specialist::factory()->create(['name' => 'سارا احمدی', 'phone' => '09120001122']);
        $a->services()->attach($service->id);
        Specialist::factory()->create(['name' => 'مریم کریمی']);

        $this->get('/s/rasta/specialists/search')->assertOk()->assertSee('سارا احمدی')->assertSee('مریم کریمی')->assertDontSee('09120001122');
        $this->get('/s/rasta/specialists/search?name=احمدی')->assertOk()->assertSee('سارا احمدی')->assertDontSee('مریم کریمی');
        $this->get("/s/rasta/specialists/search?service_id={$service->id}")->assertOk()->assertSee('سارا احمدی')->assertDontSee('مریم کریمی');
    }

    public function test_search_ignores_invalid_sort_instead_of_crashing(): void
    {
        Specialist::factory()->create();

        $this->get('/s/rasta/specialists/search?sort=password;drop&direction=sideways')->assertOk();
        $this->get('/s/rasta/specialists/search?sort=name&direction=desc')->assertOk();
        $this->get('/s/rasta/specialists/search?sort=rating')->assertOk();
    }

    public function test_search_json_is_still_available(): void
    {
        Specialist::factory()->create(['name' => 'نگار']);

        $this->getJson('/s/rasta/specialists/search')->assertOk()->assertJsonPath('data.0.name', 'نگار');
    }

    public function test_top_rated_page_is_public(): void
    {
        $this->get('/s/rasta/specialists/top-rated')->assertOk()->assertSee('هنوز متخصصی به حد نصاب امتیاز نرسیده است.');
    }

    public function test_top_rated_lists_qualifying_specialists(): void
    {
        $star = Specialist::factory()->create(['name' => 'ستاره‌ی سالن']);
        Booking::factory()->count(5)->create(['specialist_id' => $star->id, 'status' => 'completed', 'rating' => 5]);

        $this->get('/s/rasta/specialists/top-rated')->assertOk()->assertSee('ستاره‌ی سالن');
    }

    public function test_by_service_page(): void
    {
        $service = BeautyService::factory()->create(['name' => 'رنگ مو']);
        $specialist = Specialist::factory()->create(['name' => 'تینا']);
        $specialist->services()->attach($service->id);

        $this->get("/s/rasta/specialists/service/{$service->id}")->assertOk()->assertSee('رنگ مو')->assertSee('تینا');
    }

    public function test_by_service_of_another_salon_is_not_found(): void
    {
        app(CurrentSalon::class)->set(Salon::factory()->create());
        $foreign = BeautyService::factory()->create();
        app(CurrentSalon::class)->set(Salon::where('slug', 'rasta')->first());

        $this->get("/s/rasta/specialists/service/{$foreign->id}")->assertNotFound();
    }

    public function test_availability_calendar_renders_for_a_logged_in_customer(): void
    {
        $specialist = Specialist::factory()->create();
        SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => now()->addDays(2)->dayOfWeek,
            'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/s/rasta/specialists/{$specialist->id}/availability")
            ->assertOk()
            ->assertSee('نوبت‌های خالی')
            ->assertSee('نوبت خالی');
    }

    public function test_availability_tolerates_invalid_month_input(): void
    {
        $specialist = Specialist::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/s/rasta/specialists/{$specialist->id}/availability?month=13&year=abc")
            ->assertOk();
    }

    public function test_customer_header_links_to_the_specialists_page(): void
    {
        $this->get('/s/rasta')->assertOk()->assertSee(route('specialists.search'), false);
    }
}
