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
    public function test_section_labels_are_persian(): void
    {
        // بسته‌ی «ح»، ۱: سرتیترهای کوچک بخش‌ها انگلیسی بودند (Why Mahru، Features، Pricing…)
        $html = $this->get(route('central.home'))->assertOk()->getContent();

        preg_match_all('/class="kicker">([^<]*)</u', $html, $labels);
        $this->assertNotEmpty($labels[1]);
        foreach ($labels[1] as $label) {
            $this->assertDoesNotMatchRegularExpression('/[A-Za-z]/', $label, "برچسب انگلیسی: {$label}");
        }
        $this->assertStringContainsString('چرا ماهرو', $html);
    }

    public function test_treatment_courses_are_promised_only_when_the_sale_plan_has_them(): void
    {
        $plan = \App\Models\Plan::create(['key' => 'full', 'name' => 'کامل', 'price_1m' => 1500000, 'is_active' => true, 'is_public' => true, 'sms_quota_per_month' => 2000]);
        $plan->syncFeatures(array_diff(\App\Support\Features\Feature::keys(), ['treatment_courses']));
        $this->get(route('central.home'))->assertOk()->assertDontSee('یادآوری خودکار جلسه‌ی بعد');

        $plan->syncFeatures(\App\Support\Features\Feature::keys());
        $this->get(route('central.home'))->assertOk()->assertSee('یادآوری خودکار جلسه‌ی بعد');
    }

    /** پلن «کلینیک» عمومی (ادامه ۷): کارت جدا با امکانات اضافه؛ ادعای دوره با نام همان پلن؛ «کامل» دیگر «همه‌ی امکانات» نیست */
    public function test_every_public_plan_gets_its_own_card_with_what_it_adds(): void
    {
        $full = \App\Models\Plan::create(['key' => 'full', 'name' => 'کامل', 'price_1m' => 1500000, 'is_active' => true, 'is_public' => true, 'sort' => 10, 'sms_quota_per_month' => 2000]);
        $full->syncFeatures(array_diff(\App\Support\Features\Feature::keys(), ['treatment_courses', 'winback_manual']));
        $clinic = \App\Models\Plan::create(['key' => 'clinic', 'name' => 'کلینیک', 'description' => 'برای کلینیک‌های پوست و لیزر', 'price_1m' => 2400000, 'is_active' => true, 'is_public' => true, 'sort' => 40, 'sms_quota_per_month' => 4000, 'included_specialists' => 15]);
        $clinic->syncFeatures(\App\Support\Features\Feature::keys());

        $this->get(route('central.home'))->assertOk()
            // بسته‌ی «و»: لندینگ چهارپلنی — هر پلن عمومی کارت خودش را دارد و فقط چیزی که به پلن قبلی اضافه می‌کند فهرست می‌شود
            ->assertSee('data-plan-card="full"', false)
            ->assertSee('data-plan-card="clinic"', false)
            ->assertSee('۲,۴۰۰,۰۰۰')
            ->assertSee('همه‌ی امکانات کامل')
            ->assertSee('دوره‌های درمانی چندجلسه‌ای')
            ->assertSee('ارسال دستی به مشتری‌های قدیمی')
            ->assertSee('یادآوری خودکار جلسه‌ی بعد (پلن کلینیک)')
            ->assertSee('data-period-btn="12m"', false)
            ->assertDontSee('همه‌ی امکانات ماهرو');
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

    /**
     * سهمیه‌ی پیامک ماهانه است و هر ماه تازه می‌شود (SmsQuotaService::currentPeriod)؛ کارت ۳/۶/۱۲ ماهه قبلاً «۶٬۰۰۰ قطعه
     * پیامک (۲٬۰۰۰ در ماه)» می‌نوشت که متناقض و گمراه‌کننده بود (۲۰۲۶-۱۰-۱۵) — حالا همه‌ی کارت‌ها فقط عدد ماهانه.
     */
    public function test_every_plan_card_shows_the_monthly_sms_quota_only(): void
    {
        config(['billing.sms_quota_per_month' => 1500]);

        $html = $this->get(route('central.home'))->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, '۱,۵۰۰ قطعه پیامک در هر ماه'));
        foreach (['۴,۵۰۰', '۹,۰۰۰', '۱۸,۰۰۰'] as $total) {
            $this->assertStringNotContainsString($total.' قطعه پیامک', $html);
        }
    }

    /** پرسش محدودیت دوره‌ی رایگان از خود پلن آزمایشی می‌آید، نه جمله‌ی ثابت «تنها تفاوت، سقف پیامک است» */
    public function test_trial_faq_lists_what_the_trial_plan_lacks(): void
    {
        config(['billing.trial_days' => 14]);
        $this->get(route('central.home'))->assertSee('خیر؛ همه‌ی امکانات')->assertDontSee('سقف پیامک');

        $full = \App\Models\Plan::create(['key' => 'full', 'name' => 'کامل', 'price_1m' => 1500000, 'is_active' => true, 'is_public' => true, 'sms_quota_per_month' => 2000]);
        $full->syncFeatures(\App\Support\Features\Feature::keys());
        $trial = \App\Models\Plan::create(['key' => 'trial', 'name' => 'آزمایشی', 'is_trial' => true, 'is_active' => true, 'trial_days' => 14, 'sms_quota_per_month' => 400]);
        $trial->syncFeatures(array_diff(\App\Support\Features\Feature::keys(), ['sms_reminders']));

        $this->get(route('central.home'))->assertOk()
            ->assertSee('این‌ها فقط بعد از خرید اشتراک فعال می‌شوند: یادآوری پیامکی پیش از نوبت.')
            ->assertDontSee('سقف پیامک');

        $trial->syncFeatures(\App\Support\Features\Feature::keys());
        $this->get(route('central.home'))->assertSee('خیر؛ همه‌ی امکانات پلن کامل در دوره‌ی رایگان باز است.');
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

    public function test_landing_shows_the_trial_and_which_layouts_and_palettes_each_plan_and_period_opens(): void
    {
        // بسته‌ی «و» (درخواست ابوالفضل): نه فقط تعداد قالب — کدام چیدمان‌ها و پالت‌ها، در هر پلن و هر بازه، به‌علاوه‌ی کارت آزمایشی
        config(['billing.trial_days' => 14]);
        app(\App\Services\Plans\DefaultPlans::class)->sync();

        $page = $this->get(route('central.home'))->assertOk();
        $page->assertSee('data-plan-card="trial"', false)
            ->assertSee('id="templates-table"', false)
            ->assertSee('data-theme-row="trial"', false)
            ->assertSee('data-theme-row="pro"', false)
            ->assertSee('چیدمان: ویترین، مجله')
            ->assertSee('همه‌ی ۱۶ قالب')
            ->assertSee('ریل آیکونی · گرافیت');

        $pro = collect($page->viewData('planCards'))->firstWhere('key', 'pro');
        $this->assertSame(['ویترین', 'مجله'], $pro['periods']['1m']['theme_detail']['layouts']);
        $this->assertSame(['رز موکا', 'مریم‌گلی'], array_column($pro['periods']['1m']['theme_detail']['palettes'], 'name'));
        $this->assertSame(['ویترین', 'مجله', 'آرام', 'بنتو'], $pro['periods']['12m']['theme_detail']['layouts']);
        $this->assertSame(0, $page->viewData('trialCard')['theme_detail']['count']);
    }
}
