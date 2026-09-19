<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_the_latest_6_services_and_4_specialists(): void
    {
        BeautyService::factory()->count(8)->create();
        Specialist::factory()->count(6)->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertCount(6, $response->viewData('services'));
        $this->assertCount(4, $response->viewData('specialists'));
    }

    public function test_index_caches_the_service_and_specialist_lists(): void
    {
        Cache::flush();
        BeautyService::factory()->count(2)->create();

        $this->get(route('home'));

        // ⭐ کلید کش سالن‌دار — به docblock کنار همین کلیدها در HomeController نگاه کن.
        $salonId = app(CurrentSalon::class)->id();
        $this->assertTrue(Cache::has("home_services:{$salonId}"));
        $this->assertTrue(Cache::has("home_specialists:{$salonId}"));
    }

    // ---------------------------------------------------------------------
    // پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰): $currentSalonTagline/$currentSalonBio (ViewComposer)
    // ---------------------------------------------------------------------

    public function test_home_page_shows_the_salons_own_bio_when_set(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['bio' => 'این یک معرفی اختصاصی و منحصربه‌فرد برای همین سالن است.']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('این یک معرفی اختصاصی و منحصربه‌فرد برای همین سالن است.');
    }

    public function test_home_page_falls_back_to_generic_bio_when_salon_has_none(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $this->assertNull($salon->bio);

        $response = $this->get(route('home'));

        $response->assertOk();
        // ⭐ fallback متن (ViewComposer) — یک سالن تازه‌ساخته که هنوز bio ننوشته نباید صفحه‌ی
        // خالی/شکسته ببینه.
        $response->assertSee('فضایی آرام و لوکس را برای مراقبت کامل از مو، پوست و زیبایی شما فراهم کرده است');
    }

    public function test_services_page_shows_the_salons_own_tagline_when_set(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->update(['tagline' => 'یک شعار کاملاً اختصاصی']);

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('یک شعار کاملاً اختصاصی');
    }

    public function test_services_page_falls_back_to_generic_tagline_when_salon_has_none(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $this->assertNull($salon->tagline);

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('بهترین خدمات زیبایی با متخصص‌ترین تیم');
    }

    public function test_index_is_publicly_accessible_without_authentication(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_services_and_specialists_are_cached_separately_per_salon(): void
    {
        // ⭐ Regression test برای یک نشتی داده‌ی واقعی و تأییدشده‌ی بین‌سالنی (کشف‌شده
        // ۲۰۲۶-۰۹-۱۹ حین تست محور ۳ با دو سالن کنار هم): قبلاً HomeController سرویس‌ها/
        // متخصصان رو زیر یک کلید کش مشترک ذخیره می‌کرد، صرف‌نظر از این‌که صفحه‌ی اصلی کدوم
        // سالن بازدید شده — یعنی هر سالنی که اول کش می‌شد، تا ۳۰ دقیقه روی همه‌ی سالن‌های
        // دیگه هم تحمیل می‌شد.
        Cache::flush();

        $salonA = Salon::factory()->create(['slug' => 'salon-a']);
        $salonB = Salon::factory()->create(['slug' => 'salon-b']);

        app(CurrentSalon::class)->set($salonA);
        $serviceA = BeautyService::factory()->create();

        app(CurrentSalon::class)->set($salonB);
        $serviceB = BeautyService::factory()->create();

        $responseA = $this->get('/s/'.$salonA->slug.'/');
        $responseA->assertOk();
        $servicesA = $responseA->viewData('services');
        $this->assertTrue($servicesA->contains('id', $serviceA->id));
        $this->assertFalse($servicesA->contains('id', $serviceB->id));

        $responseB = $this->get('/s/'.$salonB->slug.'/');
        $responseB->assertOk();
        $servicesB = $responseB->viewData('services');
        $this->assertTrue($servicesB->contains('id', $serviceB->id));
        $this->assertFalse($servicesB->contains('id', $serviceA->id));
    }

    public function test_home_page_shows_the_visiting_salons_own_name_not_a_hardcoded_one(): void
    {
        // ⭐ Regression test برای باگ برندینگ هاردکد (کشف‌شده هم‌زمان با نشتی کش بالا): نام
        // سالن در ده‌ها ویو به‌صورت متن ثابت «راستا» نوشته شده بود، نه از دیتابیس — این تست
        // برای یک سالن با نام کاملاً متفاوت چک می‌کنه که نام واقعی‌اش توی صفحه دیده بشه.
        $salon = Salon::factory()->create(['slug' => 'salon-distinct', 'name' => 'سالن نمونه‌ی متفاوت']);
        app(CurrentSalon::class)->set($salon);

        $response = $this->get('/s/'.$salon->slug.'/');

        $response->assertOk();
        $response->assertSee('سالن نمونه‌ی متفاوت');
    }
}
