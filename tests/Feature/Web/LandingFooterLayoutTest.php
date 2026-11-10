<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پانویس صفحه‌ی فروش (۲۰۲۶-۱۱-۰۸): ستون‌های «محصول، پشتیبانی، قوانین، شبکه‌ها» روی دسکتاپ کنار هم.
 * «1.6fr repeat(auto-fit, …)» در CSS نامعتبر است (تکرار خودکار با ستون fr ترکیب نمی‌شود)، مرورگر کل grid-template-columns
 * را دور می‌ریخت و همه زیر هم می‌افتادند. حالا ستون اول ثابت و بقیه با grid-auto-flow:column؛ گوشی همان دو ستونی.
 */
class LandingFooterLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_grid_uses_valid_columns_on_desktop_and_two_columns_on_phones(): void
    {
        $this->get(route('central.home'))->assertOk()
            ->assertDontSee('repeat(auto-fit,minmax(120px,1fr))', false)
            ->assertSee('.fg{display:grid;grid-template-columns:minmax(200px,1.6fr);grid-auto-flow:column;grid-auto-columns:minmax(120px,1fr)', false)
            ->assertSee('@media(max-width:780px){.fg{grid-template-columns:1fr 1fr;grid-auto-flow:row}', false);
    }
}
