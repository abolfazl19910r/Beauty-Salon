<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\GalleryImage;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ⭐ جداسازی فایل‌های آپلودی هر سالن (۲۰۲۶-۰۹-۲۴) — در جواب سؤال ابوالفضل «عکسی که ادمین هر سالن
 * آپلود می‌کنه فقط در همون سالن دیده می‌شه؟»: رکوردها از قبل با BelongsToSalon جدا بودن
 * (CrossSalonImplicitBindingTest)؛ این فایل پوشه‌ی جدای روی دیسک و محاسبه‌ی فضای مصرفی رو پوشش می‌ده.
 */
class SalonUploadIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_gallery_upload_goes_to_the_salons_own_folder(): void
    {
        $salonId = app(CurrentSalon::class)->id();

        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'نمونه کار',
            'image' => UploadedFile::fake()->image('work.jpg', 400, 400),
        ])->assertRedirect(route('admin.gallery.index'));

        $path = GalleryImage::firstOrFail()->image_path;
        $this->assertStringStartsWith("salons/{$salonId}/gallery/", $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_service_image_upload_goes_to_the_salons_own_folder(): void
    {
        $salonId = app(CurrentSalon::class)->id();

        $this->actingAs($this->admin)->post('/admin/services', [
            'name' => 'خدمت با عکس',
            'price' => 100000,
            'duration' => 30,
            'image' => UploadedFile::fake()->image('service.jpg'),
        ]);

        $this->assertStringStartsWith(
            "salons/{$salonId}/services/",
            BeautyService::where('name', 'خدمت با عکس')->firstOrFail()->image
        );
    }

    public function test_gallery_used_space_counts_only_this_salons_images(): void
    {
        // ⭐ رگرسیون: قبلاً allFiles('gallery') کل فایل‌های همه‌ی سالن‌ها رو جمع می‌زد.
        $other = Salon::factory()->create();
        Storage::disk('public')->put('gallery/other-salon-big.jpg', str_repeat('x', 3 * 1024 * 1024));
        app(CurrentSalon::class)->set($other);
        GalleryImage::factory()->create(['image_path' => 'gallery/other-salon-big.jpg']);
        app(CurrentSalon::class)->clear();
        app(CurrentSalon::class)->set(Salon::where('slug', 'rasta')->firstOrFail());

        Storage::disk('public')->put('salons/1/gallery/mine.jpg', str_repeat('x', 1024 * 1024));
        GalleryImage::factory()->create(['image_path' => 'salons/1/gallery/mine.jpg']);

        $this->actingAs($this->admin)->get(route('admin.gallery.index'))
            ->assertOk()
            ->assertViewHas('usedSpace', 1.0)
            ->assertViewHas('imagesCount', 1);
    }

    public function test_home_page_gallery_shows_only_this_salons_images(): void
    {
        $current = app(CurrentSalon::class)->get();
        GalleryImage::factory()->create(['image_path' => 'salons/'.$current->id.'/gallery/ours.jpg']);

        $other = Salon::factory()->create();
        app(CurrentSalon::class)->set($other);
        GalleryImage::factory()->create(['image_path' => 'salons/'.$other->id.'/gallery/theirs.jpg']);
        app(CurrentSalon::class)->set($current);

        $this->get('/s/'.$current->slug)
            ->assertOk()
            ->assertDontSee('theirs.jpg', false);
    }
}
