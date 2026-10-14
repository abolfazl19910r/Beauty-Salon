<?php

namespace Tests\Feature\Branding;

use App\Models\Salon;
use App\Models\User;
use App\Services\Salon\LogoToneAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی د — خوانایی لوگو (۲۰۲۶-۱۰-۱۵): لحن روشنایی با GD، پلاک روی تمی که کنتراست کم دارد، لوگوی دوم برای پس‌زمینه‌ی تیره.
 */
class LogoLegibilityTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    /** لوگوی PNG شفاف با یک دایره‌ی پررنگ به رنگ داده‌شده؛ $opaqueBg = پس‌زمینه‌ی کامل (بدون شفافیت). */
    private static function png(array $ink, ?array $opaqueBg = null, int $size = 128): string
    {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $opaqueBg
            ? imagecolorallocate($img, ...$opaqueBg)
            : imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);
        imagefilledellipse($img, intdiv($size, 2), intdiv($size, 2), (int) ($size * 0.8), (int) ($size * 0.8), imagecolorallocate($img, ...$ink));
        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    private static function upload(string $bytes, string $name = 'logo.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => $this->salon->name, 'tagline' => 'x'], $overrides);
    }

    public function test_the_analyzer_classifies_light_dark_mid_and_opaque_logos(): void
    {
        $analyzer = new LogoToneAnalyzer;

        $this->assertSame('light', $analyzer->analyzeBytes(self::png([255, 255, 255])));
        $this->assertSame('light', $analyzer->analyzeBytes(self::png([216, 185, 106])), 'طلایی روشن روی کرم روشن کنتراست کم دارد');
        $this->assertSame('dark', $analyzer->analyzeBytes(self::png([0, 0, 0])));
        $this->assertSame('dark', $analyzer->analyzeBytes(self::png([40, 25, 20])));
        $this->assertSame('mid', $analyzer->analyzeBytes(self::png([192, 57, 43])), 'قرمز روی هر دو پس‌زمینه خواناست');
        $this->assertSame('mid', $analyzer->analyzeBytes(self::png([0, 0, 0], [255, 255, 255])), 'تصویر مات پس‌زمینه‌ی خودش را دارد');
        $this->assertNull($analyzer->analyzeBytes('not an image'));
    }

    public function test_upload_stores_the_tone_and_a_second_logo_for_dark_backgrounds(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo' => self::upload(self::png([0, 0, 0])),
        ]))->assertSessionHasNoErrors();

        $salon = $this->salon->fresh();
        $this->assertSame('dark', $salon->logo_tone);
        $this->assertSame(['url' => $salon->logoUrl(), 'plaque' => true], $salon->logoAppearance()['dark']);
        $this->assertFalse($salon->logoAppearance()['light']['plaque']);

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo_dark' => self::upload(self::png([255, 255, 255])),
        ]))->assertSessionHasNoErrors();

        $salon = $salon->fresh();
        $this->assertStringStartsWith("salons/{$salon->id}/branding/", $salon->logo_dark_path);
        $this->assertSame('light', $salon->logo_dark_tone);
        $this->assertSame(['url' => $salon->logoDarkUrl(), 'plaque' => false], $salon->logoAppearance()['dark'], 'لوگوی دوم روی تم تیره، بی‌پلاک');
    }

    public function test_the_dark_logo_needs_a_main_logo_and_goes_with_it(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo_dark' => self::upload(self::png([255, 255, 255])),
        ]))->assertSessionHasErrors('logo_dark');
        $this->assertNull($this->salon->fresh()->logo_dark_path);

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo' => self::upload(self::png([0, 0, 0])),
            'logo_dark' => self::upload(self::png([255, 255, 255])),
        ]))->assertSessionHasNoErrors();
        $salon = $this->salon->fresh();
        Storage::disk('public')->assertExists([$salon->logo_path, $salon->logo_dark_path]);
        $darkPath = $salon->logo_dark_path;

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['remove_logo' => '1']));
        $salon = $salon->fresh();
        $this->assertNull($salon->logo_path);
        $this->assertNull($salon->logo_dark_path);
        $this->assertNull($salon->logo_tone);
        Storage::disk('public')->assertMissing($darkPath);
    }

    public function test_the_dark_logo_alone_can_be_removed(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo' => self::upload(self::png([0, 0, 0])),
            'logo_dark' => self::upload(self::png([255, 255, 255])),
        ]));

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['remove_logo_dark' => '1']));

        $salon = $this->salon->fresh();
        $this->assertNotNull($salon->logo_path);
        $this->assertNull($salon->logo_dark_path);
        $this->assertNull($salon->logo_dark_tone);
    }

    public function test_the_site_shows_the_plaque_only_on_the_theme_with_low_contrast(): void
    {
        Storage::disk('public')->put('salons/x/light.png', self::png([255, 255, 255]));
        $this->salon->update(['logo_path' => 'salons/x/light.png', 'logo_tone' => 'light']);

        $html = $this->get('/s/rasta')->assertOk()->getContent();
        // تم روشن: پلاک تیره؛ تم تیره: همان لوگو بی‌پلاک — دو تصویر که CSS تم یکی را نشان می‌دهد
        $this->assertStringContainsString('data-logo-plaque="light"', $html);
        $this->assertStringNotContainsString('data-logo-plaque="dark"', $html);
        $this->assertMatchesRegularExpression('/<img[^>]*m-only-light/', $html);
        $this->assertMatchesRegularExpression('/<img[^>]*m-only-dark/', $html);
        $this->assertStringContainsString('background-color:#1C1712', $html);

        // لوگوی خوانا روی هر دو تم: یک تصویر، بی‌پلاک
        $this->salon->update(['logo_tone' => 'mid']);
        $html = $this->get('/s/rasta')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-logo-plaque', $html);
        $this->assertDoesNotMatchRegularExpression('/<img[^>]*m-only-(light|dark)/', $html);
    }

    public function test_the_light_panels_plaque_a_light_logo(): void
    {
        Storage::disk('public')->put('salons/x/light.png', self::png([255, 255, 255]));
        $this->salon->update(['logo_path' => 'salons/x/light.png', 'logo_tone' => 'light']);

        $html = $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('data-logo-plaque="light"', $html);
        $this->assertStringContainsString('data-logo-tone-preview', $html);
        $this->assertStringContainsString('لوگو روشن است', $html);
    }

    public function test_the_api_gives_the_apps_the_tone_and_the_dark_logo(): void
    {
        Storage::disk('public')->put('salons/x/a.png', self::png([0, 0, 0]));
        Storage::disk('public')->put('salons/x/b.png', self::png([255, 255, 255]));
        $this->salon->update(['logo_path' => 'salons/x/a.png', 'logo_tone' => 'dark', 'logo_dark_path' => 'salons/x/b.png', 'logo_dark_tone' => 'light']);

        $this->getJson('/api/v1/customer/salons/rasta')->assertOk()
            ->assertJsonPath('data.logo_tone', 'dark')
            ->assertJsonPath('data.logo_dark_url', Storage::disk('public')->url('salons/x/b.png'))
            ->assertJsonPath('data.logo_dark_tone', 'light');
    }

    public function test_the_command_measures_logos_uploaded_before_this_package(): void
    {
        Storage::disk('public')->put('salons/x/old.png', self::png([255, 255, 255]));
        $this->salon->update(['logo_path' => 'salons/x/old.png', 'logo_tone' => null]);
        $other = Salon::factory()->create(['logo_path' => 'salons/x/missing.png']);

        $this->artisan('salons:analyze-logos')->assertSuccessful();

        $this->assertSame('light', $this->salon->fresh()->logo_tone);
        $this->assertNull($other->fresh()->logo_tone, 'فایل گم‌شده بی‌خطا رد می‌شود');

        // سنجیده‌شده‌ها فقط با --all دوباره سنجیده می‌شوند
        $this->salon->update(['logo_tone' => 'mid']);
        $this->artisan('salons:analyze-logos')->assertSuccessful();
        $this->assertSame('mid', $this->salon->fresh()->logo_tone);
        $this->artisan('salons:analyze-logos', ['--all' => true])->assertSuccessful();
        $this->assertSame('light', $this->salon->fresh()->logo_tone);
    }
}
