<?php

namespace Tests\Feature\Admin;

use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    public function test_admin_can_upload_a_gallery_image(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'تصویر تست',
            'description' => 'توضیح',
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertRedirect(route('admin.gallery.index'));
        $this->assertDatabaseHas('gallery_images', ['title' => 'تصویر تست']);
        $image = GalleryImage::first();
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_new_image_gets_the_next_order_number(): void
    {
        GalleryImage::factory()->count(2)->create();

        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'سوم',
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $this->assertDatabaseHas('gallery_images', ['title' => 'سوم', 'order' => 3]);
    }

    public function test_destroy_deletes_the_file_from_disk_and_the_record(): void
    {
        $image = GalleryImage::factory()->create(['image_path' => 'gallery/photo.jpg']);
        Storage::disk('public')->put('gallery/photo.jpg', 'fake-content');

        $this->actingAs($this->admin)->delete("/admin/gallery/{$image->id}");

        Storage::disk('public')->assertMissing('gallery/photo.jpg');
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
    }

    public function test_move_up_swaps_order_with_the_previous_image(): void
    {
        $first = GalleryImage::factory()->create(['order' => 1]);
        $second = GalleryImage::factory()->create(['order' => 2]);

        $this->actingAs($this->admin)->put("/admin/gallery/{$second->id}/move-up");

        $this->assertDatabaseHas('gallery_images', ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas('gallery_images', ['id' => $second->id, 'order' => 1]);
    }

    public function test_move_up_on_the_first_image_is_a_no_op(): void
    {
        $first = GalleryImage::factory()->create(['order' => 1]);

        $this->actingAs($this->admin)->put("/admin/gallery/{$first->id}/move-up");

        $this->assertDatabaseHas('gallery_images', ['id' => $first->id, 'order' => 1]);
    }

    public function test_move_down_swaps_order_with_the_next_image(): void
    {
        $first = GalleryImage::factory()->create(['order' => 1]);
        $second = GalleryImage::factory()->create(['order' => 2]);

        $this->actingAs($this->admin)->put("/admin/gallery/{$first->id}/move-down");

        $this->assertDatabaseHas('gallery_images', ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas('gallery_images', ['id' => $second->id, 'order' => 1]);
    }

    public function test_upload_without_an_image_file_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/admin/gallery')
            ->post('/admin/gallery', ['title' => 'بدون تصویر']);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('gallery_images', 0);
    }

    public function test_non_admin_cannot_upload(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post('/admin/gallery', [
            'title' => 'x',
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertStatus(403);
    }

    private function serviceWithSpecialist(): array
    {
        $service = \App\Models\BeautyService::factory()->create(['name' => 'کراتینه']);
        $specialist = \App\Models\Specialist::factory()->create(['name' => 'سارا']);
        $specialist->services()->attach($service->id);

        return [$service, $specialist];
    }

    public function test_upload_can_link_a_service_and_its_specialist(): void
    {
        [$service, $specialist] = $this->serviceWithSpecialist();

        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'نمونه‌ی کراتینه',
            'image' => UploadedFile::fake()->image('photo.jpg'),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
        ])->assertRedirect(route('admin.gallery.index'));

        $image = GalleryImage::first();
        $this->assertSame($service->id, (int) $image->service_id);
        $this->assertSame($specialist->id, (int) $image->specialist_id);
        $this->assertSame('سارا', $image->specialist->name);
    }

    public function test_links_are_optional(): void
    {
        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'بی پیوند',
            'image' => UploadedFile::fake()->image('photo.jpg'),
            'service_id' => '',
            'specialist_id' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull(GalleryImage::first()->service_id);
    }

    public function test_specialist_who_does_not_offer_the_service_is_rejected(): void
    {
        [$service] = $this->serviceWithSpecialist();
        $stranger = \App\Models\Specialist::factory()->create(['name' => 'مینا']);

        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'x',
            'image' => UploadedFile::fake()->image('photo.jpg'),
            'service_id' => $service->id,
            'specialist_id' => $stranger->id,
        ])->assertSessionHasErrors('specialist_id');

        $this->assertDatabaseCount('gallery_images', 0);
    }

    public function test_another_salons_service_or_specialist_is_rejected(): void
    {
        $home = app(\App\Support\CurrentSalon::class)->get();
        $other = \App\Models\Salon::factory()->create(['slug' => 'other-gallery']);
        app(\App\Support\CurrentSalon::class)->set($other);
        $foreignService = \App\Models\BeautyService::factory()->create();
        $foreignSpecialist = \App\Models\Specialist::factory()->create();
        app(\App\Support\CurrentSalon::class)->set($home);

        $this->actingAs($this->admin)->post('/admin/gallery', [
            'title' => 'x',
            'image' => UploadedFile::fake()->image('photo.jpg'),
            'service_id' => $foreignService->id,
            'specialist_id' => $foreignSpecialist->id,
        ])->assertSessionHasErrors(['service_id', 'specialist_id']);
    }

    public function test_links_of_an_existing_image_can_be_changed_and_cleared(): void
    {
        [$service, $specialist] = $this->serviceWithSpecialist();
        $image = GalleryImage::create(['title' => 'a', 'image_path' => 'gallery/a.jpg', 'order' => 1]);

        $this->actingAs($this->admin)->put(route('admin.gallery.links', $image), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
        ])->assertRedirect(route('admin.gallery.index'));
        $this->assertSame($specialist->id, (int) $image->fresh()->specialist_id);

        $this->actingAs($this->admin)->put(route('admin.gallery.links', $image), [
            'service_id' => '',
            'specialist_id' => '',
        ]);
        $this->assertNull($image->fresh()->service_id);
        $this->assertNull($image->fresh()->specialist_id);
    }

    public function test_index_shows_link_selects_and_the_current_link(): void
    {
        [$service, $specialist] = $this->serviceWithSpecialist();
        GalleryImage::create(['title' => 'a', 'image_path' => 'gallery/a.jpg', 'order' => 1,
            'service_id' => $service->id, 'specialist_id' => $specialist->id]);

        $this->actingAs($this->admin)->get('/admin/gallery')->assertOk()
            ->assertSee('name="service_id"', false)
            ->assertSee('کراتینه · سارا');
    }

    public function test_deleting_the_service_keeps_the_image_and_clears_the_link(): void
    {
        [$service, $specialist] = $this->serviceWithSpecialist();
        $image = GalleryImage::create(['title' => 'a', 'image_path' => 'gallery/a.jpg', 'order' => 1,
            'service_id' => $service->id, 'specialist_id' => $specialist->id]);

        $service->specialists()->detach();
        $service->delete();

        $this->assertNotNull($image->fresh());
        $this->assertNull($image->fresh()->service_id);
    }
}
