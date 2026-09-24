<?php

namespace Tests\Feature\Central;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ برند خودِ پلتفرم «ماهرو» (۲۰۲۶-۰۹-۲۵): صفحه‌ی فروش، ثبت‌نام سالن و پنل سوپرادمین لوگو و نام
 * ماهرو رو نشون می‌دن و دیگه «راستا» (نام سالن دمو) رو به‌عنوان نام پلتفرم نمی‌نویسن. هر سالن
 * همچنان نام و لوگوی خودش رو داره.
 */
class PlatformBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_uses_the_mahru_logo_name_and_icons(): void
    {
        $this->get(route('central.home'))
            ->assertOk()
            ->assertSee('<title>ماهرو — نوبت‌دهی آنلاین سالن زیبایی</title>', false)
            ->assertSee('brand/mahru-logo-horizontal-on-dark.svg', false)
            ->assertSee('site.webmanifest', false)
            ->assertSee('ماهرو برای سالن شما یک سایت')
            ->assertDontSee('راستا');
    }

    public function test_landing_page_has_share_preview_tags_with_the_og_image(): void
    {
        $this->get(route('central.home'))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('brand/mahru-og.png').'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('brand/mahru-og.png')), 0, 2));
    }

    public function test_salon_signup_page_is_branded_mahru(): void
    {
        $this->get(route('salon-signup.create'))
            ->assertOk()
            ->assertSee('ساخت سالن خودتان روی ماهرو')
            ->assertSee('brand/mahru-logo-horizontal-on-dark.svg', false)
            ->assertDontSee('راستا');
    }

    public function test_super_admin_panel_shows_the_mahru_logo(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);

        $this->actingAs($superAdmin)->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('brand/mahru-logo-horizontal-on-dark.svg', false)
            ->assertDontSee('راستا SaaS');
    }

    public function test_brand_files_exist_and_the_manifest_is_valid(): void
    {
        foreach ([
            'favicon.svg', 'favicon.ico', 'favicon-32x32.png', 'apple-touch-icon.png', 'logo-512.png',
            'brand/mahru-mark.svg', 'brand/mahru-mark-on-light.svg', 'brand/mahru-mark-mono.svg', 'brand/mahru-app-icon.svg',
            'brand/mahru-logo-vertical-on-dark.svg', 'brand/mahru-logo-vertical-on-light.svg',
            'brand/mahru-logo-horizontal-on-dark.svg', 'brand/mahru-logo-horizontal-on-light.svg',
            'brand/icon-192.png', 'brand/icon-512.png', 'brand/icon-maskable-512.png',
        ] as $file) {
            $this->assertFileExists(public_path($file));
        }

        // لوگوها به هیچ فونتی وابسته نیستن (نوشته‌ها به outline تبدیل شدن)
        $this->assertStringNotContainsString('<text', file_get_contents(public_path('brand/mahru-logo-vertical-on-dark.svg')));

        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('ماهرو', $manifest['short_name']);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
            [$w, $h] = getimagesize(public_path(ltrim($icon['src'], '/')));
            $this->assertSame($icon['sizes'], "{$w}x{$h}");
        }
        $this->assertSame([180, 180], array_slice(getimagesize(public_path('apple-touch-icon.png')), 0, 2));
    }

    public function test_the_platform_name_is_separate_from_app_name(): void
    {
        $this->assertSame('ماهرو', config('brand.name'));
        $this->assertNotSame(config('brand.name'), config('app.name'), 'APP_NAME دست‌نخورده می‌مونه (کوکی session)');
    }
}
