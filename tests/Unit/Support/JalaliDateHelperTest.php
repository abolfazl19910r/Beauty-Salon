<?php

namespace Tests\Unit\Support;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * jalali_date() و ساعت (بسته‌ی «ح»، ۴ و ۹، ۲۰۲۶-۱۰-۱۹): پیش‌تر فقط Y/m/d جایگزین می‌شد و «Y/m/d H:i» در فاکتور ابرمدیر و
 * لاگ‌های امنیتی «۱۴۰۵/۰۷/۲۷ H:i» خام نشان می‌داد.
 */
class JalaliDateHelperTest extends TestCase
{
    public function test_date_only_format_is_unchanged(): void
    {
        $this->assertSame('1405/07/27', jalali_date(Carbon::parse('2026-10-19 09:05:07')));
        $this->assertSame('1405/01/01', jalali_date('2026-03-21', 'Y/m/d'));
    }

    public function test_time_letters_are_filled_from_the_same_moment(): void
    {
        $this->assertSame('1405/07/27 09:05', jalali_date(Carbon::parse('2026-10-19 09:05:07'), 'Y/m/d H:i'));
        $this->assertSame('1405/07/27 23:59:58', jalali_date('2026-10-19 23:59:58', 'Y/m/d H:i:s'));
    }

    public function test_short_letters_and_escaping(): void
    {
        $this->assertSame('5 7 05 9', jalali_date('2026-09-27 09:00', 'j n y G'));
        $this->assertSame('ساعت 14', jalali_date('2026-10-19 14:30', 'ساعت H'));
        $this->assertSame('H=14', jalali_date('2026-10-19 14:30', '\\H=H'));
    }
}
