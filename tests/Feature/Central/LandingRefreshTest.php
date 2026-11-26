<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۶ (۲۰۲۶-۱۱-۲۶): به‌روزرسانی ظاهری لندینگ طبق پیش‌نمایش تأییدشده‌ی بوم. HTML بی جاوااسکریپت همان چیزی است که
 * Googlebot پیش از رندر می‌بیند؛ پس شمارنده‌ی «۰»، ایموجی و رقم لاتین در خود HTML نباید باشد.
 */
class LandingRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function markup(): string
    {
        $html = $this->get(route('central.home'))->assertOk()->getContent();
        $html = preg_replace('/<script\b.*?<\/script>/su', '', $html);
        $html = preg_replace('/<style\b.*?<\/style>/su', '', $html);

        return preg_replace('/<svg\b.*?<\/svg>/su', '', $html);
    }

    public function test_no_zero_counters_emoji_or_latin_clock_times_in_the_markup(): void
    {
        $html = $this->markup();

        $this->assertStringNotContainsString('data-to=', $html);
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2728}]/u', $html), 'ایموجی در لندینگ');
        $text = strip_tags($html);
        $this->assertSame(0, preg_match('/\b\d{1,2}:\d{2}\b/', $text, $m), 'ساعت لاتین: '.($m[0] ?? ''));
        $this->assertStringContainsString('۰۱:۴۰', $text);
    }

    public function test_only_the_final_section_title_is_gold_and_no_section_has_a_kicker(): void
    {
        $html = $this->markup();

        $this->assertStringNotContainsString('class="kicker"', $html);
        preg_match_all('/<h2[^>]*>.*?<\/h2>/su', $html, $titles);
        $gold = array_values(array_filter($titles[0], fn (string $t) => str_contains($t, 'class="gold"')));
        $this->assertCount(1, $gold);
        $this->assertStringContainsString('درخشان‌تر', $gold[0]);
    }

    public function test_heavy_background_effects_are_off_on_phones(): void
    {
        $html = $this->get(route('central.home'))->getContent();

        $this->assertStringContainsString('@media(max-width:767px){.aur,.grain{display:none}}', $html);
    }

    public function test_header_has_one_login_menu_and_one_main_invitation(): void
    {
        config(['billing.trial_days' => 14]);
        $html = $this->get(route('central.home'))->assertOk()->getContent();
        $header = substr($html, strpos($html, '<header id="hd">'), strpos($html, '</header>') - strpos($html, '<header id="hd">'));

        $this->assertStringContainsString('data-login-menu', $header);
        $this->assertStringContainsString('href="'.route('login').'">ورود کادر سالن', $header);
        $this->assertStringContainsString('href="'.route('my-salons.show').'">سالن‌های من', $header);
        $this->assertSame(1, substr_count($header, 'btn-gold'));
        $this->assertStringContainsString('href="'.route('salon-signup.create').'">شروع رایگان</a>', $header);
        $this->assertStringNotContainsString('درخواست دمو', $header);
        preg_match_all('/<nav class="links".*?<\/nav>/su', $header, $nav);
        $this->assertSame(5, substr_count($nav[0][0], '<a '));
        // فرم دمو در بخش پایانی می‌ماند و منوی موبایل یک لینک متنی به آن دارد
        $this->assertStringContainsString('id="demoForm"', $html);
        $this->assertStringContainsString('class="mnav-demo" href="#start">درخواست دمو', $html);
    }

    public function test_hero_has_a_two_line_title_and_the_no_card_note(): void
    {
        config(['billing.trial_days' => 14]);
        $html = $this->get(route('central.home'))->assertOk()->getContent();

        $this->assertStringContainsString('<span class="h1a">سالن شما، همیشه پر از نوبت</span><span class="h1b">بدون یک تماس بی‌پاسخ</span>', $html);
        $this->assertStringContainsString('بدون کارت بانکی.', $html);
        $this->assertStringContainsString('<ul class="proof">', $html);
        $this->assertStringContainsString('شروع رایگان ۱۴ روزه · راه‌اندازی در یک روز', $html);

        config(['billing.trial_days' => 0]);
        $this->get(route('central.home'))->assertDontSee('بدون کارت بانکی.')->assertSee('ساخت سالن · راه‌اندازی در یک روز');
    }

    public function test_plan_cards_share_one_shape_and_show_only_theme_counts(): void
    {
        config(['billing.trial_days' => 14]);
        app(\App\Services\Plans\DefaultPlans::class)->sync();
        $html = $this->get(route('central.home'))->assertOk()->getContent();
        $cards = substr($html, strpos($html, '<div class="prc p4">'), strpos($html, '<details class="tt rv"') - strpos($html, '<div class="prc p4">'));

        $this->assertSame(4, substr_count($cards, 'data-plan-card="'));
        $this->assertStringContainsString('برای سالن کوچک: رزرو آنلاین و پیامک‌های نوبت.', $cards);
        $this->assertStringContainsString('همه‌ی امکانات پایه، به‌اضافه‌ی:', $cards);
        $this->assertStringContainsString('تومان در ماه', $cards);
        $this->assertStringContainsString('۴ قالب سایت و ۱ قالب پنل', $cards);
        $this->assertStringContainsString('همه‌ی ۱۶ قالب سایت و ۳ قالب پنل', $cards);
        // نام چیدمان و پالت فقط در جدول «مقایسه‌ی کامل»
        $this->assertStringNotContainsString('چیدمان:', $cards);
        $this->assertStringNotContainsString('class="sw"', $cards);
        $this->assertStringContainsString('<summary>مقایسه‌ی کامل امکانات و قالب‌ها در هر پلن و مدت</summary>', $html);
        $this->assertStringContainsString('هر کارشناس بیشتر ماهی', $html);
        // آزمون Chrome ویندوز: قیمت دوازده‌ماهه («۲۲٬۱۵۰٬۰۰۰ تومان برای دوازده ماه») کارت چهارم را از صفحه بیرون می‌برد
        $this->assertStringContainsString('@media(min-width:1181px){.prc.p4{grid-template-columns:repeat(4,minmax(0,1fr))}}', $html);
    }

    public function test_sync_replaces_only_untouched_old_default_descriptions(): void
    {
        app(\App\Services\Plans\DefaultPlans::class)->sync();
        \App\Models\Plan::byKey('pro')->update(['description' => 'همه‌ی امکانات اصلی سالن، با ۱۰ متخصص و ۲٬۰۰۰ پیامک در ماه.']);
        \App\Models\Plan::byKey('basic')->update(['description' => 'رزرو آنلاین و پیامک‌های نوبت برای سالن کوچک.']);
        \App\Models\Plan::byKey('clinic')->update(['description' => 'متن دلخواه ابرمدیر']);

        $result = app(\App\Services\Plans\DefaultPlans::class)->sync();

        $this->assertEqualsCanonicalizing(['pro', 'basic'], $result['descriptions_updated']);
        $this->assertSame('همه‌ی امکانات اصلی یک سالن پرکار.', \App\Models\Plan::byKey('pro')->description);
        $this->assertSame('برای سالن کوچک: رزرو آنلاین و پیامک‌های نوبت.', \App\Models\Plan::byKey('basic')->description);
        $this->assertSame('متن دلخواه ابرمدیر', \App\Models\Plan::byKey('clinic')->description);
        $this->assertSame([], app(\App\Services\Plans\DefaultPlans::class)->sync()['descriptions_updated']);
    }
}
