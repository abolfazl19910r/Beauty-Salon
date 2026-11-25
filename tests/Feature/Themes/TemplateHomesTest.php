<?php

namespace Tests\Feature\Themes;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\GalleryImage;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use App\Support\Themes\SalonAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * بسته‌ی ۵الف: صفحه‌ی خانه‌ی «شاهکار» و «اپ» با داده‌ی واقعی (فرم رزرو، ساعت‌های خالی، منوی خدمات، کارت سالن، کاشی‌ها).
 */
class TemplateHomesTest extends TestCase
{
    use RefreshDatabase;

    private BeautyService $service;

    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-24 18:10:00'));
        app(DefaultPlans::class)->sync();
        $salon = app(CurrentSalon::class)->get();
        $salon->forceFill([
            'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '12m', 'trial_ends_at' => null, 'is_suspended' => false, 'subscription_ends_at' => now()->addYear(),
            'tagline' => 'زیبایی با آرامش', 'established_year' => now()->year - 11, 'phone' => '02100000002',
            'working_hours' => [2 => ['open' => '09:00', 'close' => '21:00']],
        ])->save();
        $category = Category::factory()->create(['name' => 'مو']);
        $this->service = BeautyService::factory()->create(['name' => 'رنگ کامل', 'duration' => 60, 'price' => 590000, 'category_id' => $category->id]);
        $this->specialist = Specialist::factory()->create(['name' => 'سارا']);
        $this->service->specialists()->attach($this->specialist->id);
        SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id, 'day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '21:00']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function choose(string $key): void
    {
        app(CurrentSalon::class)->get()->forceFill(['site_theme' => $key])->save();
        SalonAppearance::flush();
        Cache::flush();
    }

    public function test_signature_home_has_the_booking_form_with_real_slots_and_numbers(): void
    {
        $this->choose('signature:rose');
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sg-form', $html);
        $this->assertStringContainsString('زیبایی با آرامش', $html, 'تیتر از tagline');
        $this->assertStringContainsString('ادامه و ثبت، امروز ۱۸:۳۰', $html);
        $this->assertStringContainsString('name="date" value="2026-11-24"', $html);
        $this->assertStringContainsString('name="time" value="18:30"', $html);
        $this->assertStringContainsString('name="specialist" value="'.$this->specialist->id.'"', $html);
        $this->assertStringContainsString('<option value="'.$this->specialist->id.'">سارا</option>', $html);
        $this->assertStringContainsString('۱۱</b><div>سال سابقه', $html);
        $this->assertStringContainsString('منوی خدمات', $html);
        $this->assertStringContainsString('۵۹۰٬۰۰۰', $html);
        $this->assertStringContainsString('نوبت خالی: امروز ۱۸:۳۰', $html);
        $this->assertStringNotContainsString('از ۰ نظر', $html, 'بی نظر، بی عدد امتیاز');
        $this->assertStringContainsString('data-palette-art', $html, 'کارشناس بی عکس ← طرح پالت');
    }

    public function test_signature_without_free_slots_falls_back_to_the_booking_form(): void
    {
        $this->specialist->schedules()->delete();
        $this->choose('signature:sage');

        $this->get(route('home'))->assertOk()->assertSee('انتخاب روز و ساعت')->assertDontSee('نوبت خالی:');
    }

    public function test_app_home_has_the_salon_card_free_band_and_tiles(): void
    {
        Review::factory()->create(['is_approved' => true, 'overall_rating' => 4]);
        GalleryImage::factory()->count(3)->create(['is_active' => true]);
        $this->choose('app:lilac');
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('data-ap-salon-card', $html);
        $this->assertStringContainsString('باز تا ۲۱', $html);
        $this->assertStringContainsString('۴ از ۱ نظر', $html);
        $this->assertStringContainsString('href="tel:02100000002"', $html);
        $this->assertStringContainsString('data-ap-free', $html);
        $this->assertStringContainsString('date=2026-11-24&amp;time=18%3A30', $html);
        $this->assertStringContainsString('رنگ کامل', $html);
        $this->assertStringContainsString('همه‌ی خدمات و قیمت‌ها', $html);
        $this->assertStringContainsString('نمونه‌کارها، ۳ عکس', $html);
    }

    public function test_app_hides_blocks_without_data(): void
    {
        $this->specialist->schedules()->delete();
        app(CurrentSalon::class)->get()->forceFill(['working_hours' => null])->save();
        $this->choose('app:turquoise');

        $this->get(route('home'))->assertOk()
            ->assertDontSee('data-ap-free', false)->assertDontSee('data-ap-today', false)->assertDontSee('data-ap-gallery', false)
            ->assertDontSee('نظرها');
    }

    public function test_service_pages_use_the_palette_art_and_button_on_paid_templates(): void
    {
        $this->choose('signature:rose');
        $this->get(route('services.index'))->assertOk()->assertSee('data-palette-art', false)
            ->assertDontSee('placeholder-service.svg')->assertDontSee('from-[#C9A24B]', false);
        $this->get(route('services.show', $this->service))->assertOk()->assertSee('data-palette-art', false)->assertDontSee('placeholder-service.svg');

        app(CurrentSalon::class)->get()->forceFill(['site_theme' => null])->save();
        SalonAppearance::flush();
        $this->get(route('services.index'))->assertOk()->assertSee('placeholder-service.svg')->assertSee('from-[#C9A24B]', false);
    }
}
