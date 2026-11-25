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
 * بسته‌ی ۵الف/۵ب: صفحه‌ی خانه‌ی «شاهکار»، «اپ»، «نمایشگاه» و «روزنامه‌ی سالن» با داده‌ی واقعی.
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
        $this->assertMatchesRegularExpression('/data-palette-art[^>]*data-ap-gallery|data-ap-gallery[^>]*data-palette-art/', $html, 'کاشی گالری خودش طرح پالت است (نه لایه‌ی absolute)');
        $this->assertStringContainsString('flex flex-col overflow-x-clip', $html, 'بی اسکرول افقی با نوار پیمایش کلاسیک ویندوز');
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

    public function test_showcase_home_has_the_free_slot_card_catalog_and_gallery_frame(): void
    {
        GalleryImage::factory()->create(['is_active' => true, 'order' => 1, 'title' => 'نمونه‌ی اول',
            'service_id' => $this->service->id, 'specialist_id' => $this->specialist->id]);
        GalleryImage::factory()->create(['is_active' => true, 'order' => 2, 'title' => 'نمونه‌ی دوم']);
        $this->choose('showcase:turquoise');
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('data-template-home="showcase"', $html);
        $this->assertStringContainsString('class="fixed top-0 inset-x-0 z-50 sc-hdr"', $html, 'سربرگ «نمایشگاه»');
        $this->assertStringContainsString('زیبایی با آرامش', $html);
        $this->assertStringContainsString('نزدیک‌ترین نوبت خالی', $html);
        $this->assertStringContainsString('امروز، ۱۸:۳۰', $html);
        $this->assertStringContainsString('date=2026-11-24&amp;time=18%3A30', $html);
        $this->assertStringContainsString('۶۰ دقیقه، <span data-sc-samples>۱ نمونه‌کار</span>', $html, 'شمار نمونه‌کارهای پیوندخورده به خدمت');
        $this->assertStringContainsString('نمونه‌کار ۱ از ۲، رنگ کامل', $html);
        $this->assertStringContainsString('این خدمت را با همین کارشناس رزرو کنید', $html);
        $this->assertStringContainsString('service='.$this->service->id.'&amp;specialist='.$this->specialist->id, $html);
        $this->assertStringContainsString('data-sc-expert>سارا</span>', $html);
        $this->assertStringContainsString('data-sc-thumb="1"', $html);
        $this->assertStringContainsString('نوبت خالی: امروز ۱۸:۳۰', $html);
    }

    public function test_showcase_frame_falls_back_to_the_cover_then_palette_art(): void
    {
        $this->specialist->schedules()->delete();
        app(CurrentSalon::class)->get()->forceFill(['cover_path' => 'salons/1/branding/cover.jpg'])->save();
        $this->choose('showcase:rose');
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('branding/cover.jpg', $html);
        $this->assertStringNotContainsString('data-sc-thumb="', $html);
        $this->assertStringNotContainsString('<span data-sc-samples>', $html);
        $this->assertStringContainsString('روز و ساعت دلخواه', $html, 'بی نوبت خالی، کارت نوبت آنلاین');
        $this->assertStringContainsString('نوبت بعدی‌تان را رزرو کنید', $html);

        app(CurrentSalon::class)->get()->forceFill(['cover_path' => null])->save();
        Cache::flush();
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('branding/cover.jpg', $html);
        $this->assertMatchesRegularExpression('/class="m-art sc-frame__art"[^>]*data-palette-art/', $html);
    }

    public function test_gallery_changes_refresh_the_showcase_frame(): void
    {
        $this->choose('showcase:sage');
        $this->get(route('home'))->assertOk()->assertDontSee('نمونه‌کار ۱ از');

        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs($admin)->post('/admin/gallery', [
            'title' => 'تازه', 'image' => \Illuminate\Http\UploadedFile::fake()->image('a.jpg'),
        ]);
        auth()->logout();

        $this->get(route('home'))->assertOk()->assertSee('نمونه‌کار ۱ از ۱، تازه');
    }
}
