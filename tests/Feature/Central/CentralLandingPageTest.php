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
            ->assertSee('پنل متخصص')
            ->assertSee('پنل مدیریت')
            ->assertSee('پیش‌نمایش پلن');
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
