<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ صفحه‌ی اصلی/فروش دامنه‌ی مرکزی (۲۰۲۶-۰۹-۲۳). همه‌ی اعداد صفحه باید از config/billing.php
 * بیان — هیچ قیمت/سقفی هاردکد نیست — تا صفحه‌ی فروش هیچ‌وقت با قیمت واقعی خرید از پنل فرق نکنه.
 */
class CentralLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_public_and_links_to_signup_and_login(): void
    {
        $this->get(route('central.home'))
            ->assertOk()
            ->assertSee(route('salon-signup.create'), false)
            ->assertSee(route('login'), false)
            ->assertSee('پنل مدیریت')
            ->assertSee('action="'.route('demo-requests.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee(asset('brand/mahru-og.png'), false)
            // فونت‌ها روی همین سرور؛ هیچ CDN خارجی (MAHRU_UI_PROMPT قانون ۴)
            ->assertSee(asset('fonts/mahru/Vazirmatn-Regular.woff2'), false)
            ->assertDontSee('fonts.googleapis.com', false);
        foreach (['Vazirmatn-Regular', 'Vazirmatn-ExtraBold', 'Sora-600'] as $font) {
            $this->assertFileExists(public_path("fonts/mahru/{$font}.woff2"));
        }
    }

    /**
     * لندینگ تازه (۲۰۲۶-۱۰-۱۴) از فایل طراح آمد که نام «مهرو»، پلن‌های «پایه/حرفه‌ای/مجموعه»، شماره‌ی نمونه و چند ادعای
     * بی‌پشتوانه داشت. نام فارسی «ماهرو» می‌ماند و صفحه فقط امکاناتی را وعده می‌دهد که نرم‌افزار دارد.
     */
    public function test_new_landing_keeps_the_brand_name_and_promises_only_real_features(): void
    {
        $html = $this->get(route('central.home'))->assertOk()->getContent();

        $this->assertStringContainsString('ماهرو', $html);
        $this->assertSame(0, preg_match('/(?<!ا)مهرو/u', $html), 'نام فارسی «ماهرو» است، نه «مهرو»');
        // پیگیری مشتریان غایب و متن پیامک قابل ویرایش از ۲۰۲۶-۱۰-۱۴ واقعاً هستند (WinbackService، SmsTemplates)
        $this->assertStringContainsString('پیگیری خودکار مشتریانی که مدتی نیامده‌اند', $html);
        $this->assertStringContainsString('متن پیامک‌های نوبت و باشگاه را هم می‌توانید', $html);
        foreach (['چند شعبه، یک پنل', 'شعبه‌های نامحدود', 'یادآوری خودکار جلسه‌ی بعد', 'کاملاً قابل تنظیم', '۲۹۰٬۰۰۰', '۵۹۰٬۰۰۰', '0900 000 0000', 'href="#">اینستاگرام'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, $claim);
        }
    }

    /** دوره‌های درمانی (بسته‌ی ب) فقط وقتی پلن فروش امکان را دارد؛ برداشتن تیک امکان در پنل سوپرادمین ادعا را برمی‌دارد */
    public function test_treatment_courses_are_promised_only_when_the_sale_plan_has_them(): void
    {
        $plan = \App\Models\Plan::create(['key' => 'full', 'name' => 'کامل', 'price_1m' => 1500000, 'is_active' => true, 'is_public' => true, 'sms_quota_per_month' => 2000]);
        $plan->syncFeatures(array_diff(\App\Support\Features\Feature::keys(), ['treatment_courses']));
        $this->get(route('central.home'))->assertOk()->assertDontSee('یادآوری خودکار جلسه‌ی بعد');

        $plan->syncFeatures(\App\Support\Features\Feature::keys());
        $this->get(route('central.home'))->assertOk()->assertSee('یادآوری خودکار جلسه‌ی بعد');
    }

    public function test_contact_links_appear_only_when_configured(): void
    {
        $this->get(route('central.home'))->assertDontSee('tel:', false)->assertDontSee('>اینستاگرام<', false);

        config(['brand.support_phone' => '02112345678', 'brand.instagram_url' => 'https://instagram.com/mahru.ir']);
        $this->get(route('central.home'))
            ->assertSee('href="tel:02112345678"', false)
            ->assertSee('href="https://instagram.com/mahru.ir"', false);
    }

    public function test_plan_prices_come_from_billing_config(): void
    {
        config(['billing.subscription_prices' => ['1m' => 1000000, '3m' => 2700000, '6m' => 5100000, '12m' => 9000000]]);

        $response = $this->get(route('central.home'))->assertOk();

        $plans = collect($response->viewData('plans'))->keyBy('type');
        $this->assertSame(['1m', '3m', '6m', '12m'], $plans->keys()->all());
        $this->assertSame(2700000, $plans['3m']['price']);
        $this->assertSame(900000, $plans['3m']['per_month']);
        $this->assertSame(300000, $plans['3m']['saving']);
        $this->assertSame(10, $plans['3m']['saving_percent']);
        $this->assertSame(0, $plans['1m']['saving']);
        $this->assertSame(route('salon-signup.create', ['plan' => '6m']), $plans['6m']['signup_url']);
        $response->assertSee('۲,۷۰۰,۰۰۰');
    }

    public function test_sms_total_scales_with_plan_months(): void
    {
        config(['billing.sms_quota_per_month' => 1500]);

        $plans = collect($this->get(route('central.home'))->viewData('plans'))->keyBy('type');

        $this->assertSame(1500, $plans['1m']['sms_total']);
        $this->assertSame(18000, $plans['12m']['sms_total']);
    }

    public function test_trial_messaging_follows_trial_days_config(): void
    {
        config(['billing.trial_days' => 14]);
        $this->get(route('central.home'))->assertSee('۱۴ روز رایگان');

        config(['billing.trial_days' => 0]);
        $this->get(route('central.home'))
            ->assertDontSee('روز رایگان')
            ->assertDontSee('بعد از تمام شدن دوره‌ی رایگان');
    }

    public function test_landing_page_does_not_leak_any_salon_data(): void
    {
        // ⭐ این صفحه بیرون از هر سالنه — نام/آدرس هیچ سالن واقعی‌ای نباید روش ظاهر بشه.
        \App\Models\Salon::factory()->create(['name' => 'سالن کاملاً محرمانه', 'slug' => 'secret-salon-xyz']);

        $this->get(route('central.home'))
            ->assertOk()
            ->assertDontSee('سالن کاملاً محرمانه')
            ->assertDontSee('secret-salon-xyz');
    }
}
