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
}
