<?php

namespace Tests\Unit\Support;

use App\Support\SalonTitle;
use PHPUnit\Framework\TestCase;

/** نام زیر آیکون صفحه‌ی اصلی گوشی (PWA): بی پیشوند نوع کسب‌وکار (گزارش ۲۰۲۶-۱۰-۲۱: آیفون «سالن زیبایی…» نشان می‌داد). */
class SalonTitleShortTest extends TestCase
{
    public function test_short_names(): void
    {
        $this->assertSame('راستا', SalonTitle::short('سالن زیبایی راستا'));
        $this->assertSame('راستا', SalonTitle::short('سالن‌زیبایی راستا'));
        $this->assertSame('گلبهار', SalonTitle::short('آرایشگاه زنانه گلبهار'));
        $this->assertSame('مهتاب', SalonTitle::short('كلينيك پوست و مو مهتاب'));
        $this->assertSame('ماه‌بانو', SalonTitle::short('ماه‌بانو'));
        $this->assertSame('سالن زیبایی', SalonTitle::short('سالن زیبایی'));
    }
}
