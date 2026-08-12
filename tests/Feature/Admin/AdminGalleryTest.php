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
}
