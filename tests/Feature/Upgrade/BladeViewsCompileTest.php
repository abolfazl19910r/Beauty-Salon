<?php

namespace Tests\Feature\Upgrade;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * هر نمای Blade با کامپایلر همین نسخه‌ی Laravel به PHP معتبر تبدیل می‌شود (بسته‌ی 5ج، ارتقای Laravel).
 *
 * چرا جدا: Laravel 12 دستور تازه‌ی @context آورد و JSON-LD صفحه‌ی فروش ("@context":"https://schema.org") را به یک
 * if باز تبدیل کرد → ۵۰۰ روی دامنه‌ی اصلی. سوییت آن را ندید، چون نماهای کامپایل‌شده‌ی storage/framework/views از
 * Laravel 11 مانده بودند (Blade فقط با تغییر فایل خودِ نما دوباره کامپایل می‌کند، نه با ارتقای فریم‌ورک). این تست از
 * cache رد می‌شود: متن هر نما را مستقیم کامپایل و با token_get_all(TOKEN_PARSE) تجزیه می‌کند.
 */
class BladeViewsCompileTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $broken = [];
        $count = 0;

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $count++;
            try {
                token_get_all(Blade::compileString($file->getContents()), TOKEN_PARSE);
            } catch (\ParseError $e) {
                $broken[] = $file->getRelativePathname().': '.$e->getMessage();
            }
        }

        $this->assertGreaterThan(300, $count);
        $this->assertSame([], $broken);
    }

    public function test_the_landing_page_keeps_its_json_ld_context(): void
    {
        $this->get('/')->assertOk()->assertSee('{"@context":"https://schema.org","@type":"SoftwareApplication"', false);
    }
}
