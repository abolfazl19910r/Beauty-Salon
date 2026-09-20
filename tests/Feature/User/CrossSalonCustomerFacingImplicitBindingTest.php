<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\BlogPost;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Fix (ممیزی implicit-binding مشابه پنل ادمین، این‌بار روی روت‌های مشتری/customer-facing،
 * ۲۰۲۶-۰۹-۲۰): همون باگی که قبلاً کل پنل ادمین رو گرفته بود (SubstituteBindings — بخشی از گروه
 * global middleware `web` — پارامترهای implicit-bound {specialist}/{service} رو از طریق
 * Route::bind() سراسری در RouteServiceProvider resolve می‌کنه *قبل از* اینکه salon.resolve
 * CurrentSalon رو ست کنه) روی این ۸ متد کنترلر مشتری هم پیدا شد. با probe مستقیم HTTP تأیید شد
 * (نه فرض): specialists.show یک specialist متعلق به سالن دیگه رو کامل با salon_id واقعیش
 * برمی‌گردوند؛ reviews.specialist و blog.show هم داده‌ی واقعی سالن دیگه رو نشون می‌دادن.
 *
 * فیکس دقیقاً همون الگوی از قبل موجود در پروژه: Controller::ensureSalonOwnership($model->salon_id)
 * به‌عنوان اولین خط هر متد، بلافاصله بعد از resolve شدن مدل (چه implicit binding خودکار، چه
 * resolveSpecialist()/resolveService() دستی).
 *
 * الگوی تست: هر رکورد داخل $otherSalon ساخته می‌شود، سپس CurrentSalon کاملاً clear می‌شود — دقیقاً
 * شبیه‌سازی لحظه‌ی SubstituteBindings در یک request واقعی و تازه (همون الگوی
 * CrossSalonImplicitBindingTest برای پنل ادمین).
 */
class CrossSalonCustomerFacingImplicitBindingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Salon $otherSalon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(); // customer of the default test salon
        $this->otherSalon = Salon::factory()->create(['slug' => 'other-salon-customer-binding-test']);
    }

    private function createInOtherSalon(\Closure $factory)
    {
        app(CurrentSalon::class)->set($this->otherSalon);
        $record = $factory();
        app(CurrentSalon::class)->clear();

        return $record;
    }

    public function test_cannot_view_another_salons_service(): void
    {
        $service = $this->createInOtherSalon(fn () => BeautyService::factory()->create());

        $this->get(route('services.show', ['service' => $service->id]))->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist_profile(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->getJson(route('specialists.show', ['specialist' => $specialist->id]))->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist_availability(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->customer)
            ->getJson(route('specialists.availability', ['specialist' => $specialist->id]))
            ->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist_available_slots(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->customer)
            ->getJson(route('specialists.available-slots', ['specialist' => $specialist->id, 'date' => now()->addDay()->format('Y-m-d')]))
            ->assertNotFound();
    }

    public function test_cannot_list_specialists_by_another_salons_service(): void
    {
        $service = $this->createInOtherSalon(fn () => BeautyService::factory()->create());

        $this->getJson(route('specialists.by-service-web', ['service' => $service->id]))->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist_reviews(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->get(route('reviews.specialist', ['specialist' => $specialist->id]))->assertNotFound();
    }

    public function test_cannot_view_another_salons_blog_post(): void
    {
        $post = $this->createInOtherSalon(fn () => BlogPost::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]));

        $this->get(route('blog.show', ['post' => $post->slug]))->assertNotFound();
    }

    public function test_cannot_get_another_salons_specialist_available_dates_via_booking_flow(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->customer)
            ->getJson(route('bookings.available-dates', ['specialist' => $specialist->id]))
            ->assertNotFound();
    }

    public function test_cannot_get_another_salons_specialist_time_slots_via_booking_flow(): void
    {
        $specialist = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->customer)
            ->getJson(route('bookings.available-slots', ['specialist' => $specialist->id, 'date' => now()->addDay()->format('Y-m-d')]))
            ->assertNotFound();
    }

    public function test_cannot_list_specialists_by_another_salons_service_via_booking_flow(): void
    {
        $service = $this->createInOtherSalon(fn () => BeautyService::factory()->create());

        $this->actingAs($this->customer)
            ->getJson(route('bookings.service-specialists', ['service' => $service->id]))
            ->assertNotFound();
    }
}
