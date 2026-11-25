<?php

namespace Tests\Unit\Support;

use App\Support\Fa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FaTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_digits_become_persian(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', Fa::digits('0123456789'));
        $this->assertSame('۱۲۳', Fa::digits('١٢٣'));
        $this->assertSame('09121234567', Fa::latin('۰۹۱۲۱۲۳۴۵۶۷'));
    }

    public function test_numbers_use_the_persian_thousands_separator(): void
    {
        $this->assertSame('۷٬۸۰۷٬۲۰۰', Fa::number(7807200));
        $this->assertSame('۰', Fa::number(null));
        $this->assertSame('۱۰٫۵', Fa::number(10.5, 2));
        $this->assertSame('۱۰', Fa::number('10.00', 2));
        $this->assertSame('۱۰٪', Fa::percent(10));
        $this->assertSame('۱٬۲۰۰٬۰۰۰ تومان', Fa::money(1200000, unit: true));
    }

    public function test_text_is_never_turned_into_a_number(): void
    {
        $this->assertSame('رنگ کامل مو', Fa::number('رنگ کامل مو'));
        $this->assertSame('۱۴۰۵/۰۹/۰۹', Fa::number('1405/09/09'));
        $this->assertSame('۱۳:۰۰', Fa::number('13:00'));
        $this->assertSame('۱۲۰ دقیقه', Fa::number('120 دقیقه'));
        $this->assertSame('۱٬۲۳۴', Fa::number('1,234'));
        $this->assertSame('۱٬۲۳۴', Fa::number('۱۲۳۴'));
        $this->assertSame('۲۰۰٬۰۰۰', Fa::number('200000.00'));
    }

    public function test_identifiers_keep_their_leading_zero_and_get_no_separator(): void
    {
        $this->assertSame('۰۹۱۲۳۴۵۶۷۸۹', Fa::number('09123456789'));
        $this->assertSame('۰۰۱۲۳۴۵۶۷۸', Fa::number('0012345678'));
        $this->assertSame('۱e۵', Fa::number('1e5'));
        $this->assertSame('۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶', Fa::number('1234567890123456'));
        $this->assertSame('۰', Fa::number('0'));
        $this->assertSame('۰٫۵', Fa::number('0.5', 1));
        $this->assertSame('۱۵', Fa::number('15'));
    }

    public function test_signed_numbers_stay_glued_to_their_sign_in_rtl(): void
    {
        $this->assertSame("\u{2066}−۵۰٬۰۰۰\u{2069}", Fa::money(-50000));
        $this->assertSame("\u{2066}+۵۰٬۰۰۰\u{2069}", Fa::money(50000, signed: true));
        $this->assertSame('۰', Fa::money(0, signed: true));
        $this->assertSame('۵۰٬۰۰۰', Fa::money(50000));
    }

    public function test_dates_this_year_show_the_weekday_with_a_zwnj(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $this->assertSame('سه‌شنبه ۳ آذر', Fa::date(Carbon::parse('2026-11-24 14:30')));
        $this->assertSame('سه‌شنبه ۳ آذر، ۱۴:۳۰', Fa::date('2026-11-24 14:30', withTime: true));
        $this->assertSame('پنج‌شنبه ۵ آذر', Fa::date('2026-11-26'));
        $this->assertSame('۳ آذر', Fa::date('2026-11-24', weekday: false));
        $this->assertSame('۱۴:۳۰', Fa::time('2026-11-24 14:30'));
        $this->assertSame('۱۴۰۵/۰۹/۰۳', Fa::jdate('2026-11-24'));
    }

    public function test_dates_of_other_years_show_the_year_without_weekday(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $this->assertSame('۳ آذر ۱۴۰۴', Fa::date('2025-11-24'));
        $this->assertSame('—', Fa::date(null));
        $this->assertSame('—', Fa::time(''));
    }

    public function test_helpers_and_blade_directives(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $this->assertSame('۱٬۲۳۴', fa_num(1234));
        $this->assertSame('۱٬۲۳۴ تومان', fa_money(1234, true));
        $this->assertSame('سه‌شنبه ۳ آذر', fa_date('2026-11-24'));

        $this->assertSame('۱٬۲۳۴|۵٬۰۰۰|سه‌شنبه ۳ آذر', Blade::render('@fa($a)|@money($b)|@faDate($c)', ['a' => 1234, 'b' => 5000, 'c' => '2026-11-24']));
    }
}
