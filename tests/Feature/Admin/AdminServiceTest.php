<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_services_with_category_loaded(): void
    {
        BeautyService::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/admin/services');

        $response->assertOk();
        $this->assertCount(2, $response->viewData('services'));
    }

    public function test_store_creates_a_service(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/services', [
            'name' => 'کوتاهی مو',
            'description' => 'کوتاهی مو با تکنیک مدرن',
            'price' => 250000,
            'duration' => 45,
            'category_id' => $category->id,
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('beauty_services', ['name' => 'کوتاهی مو', 'price' => 250000]);
    }

    public function test_store_uploads_and_persists_an_image(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('service.jpg');

        $this->actingAs($this->admin)->post('/admin/services', [
            'name' => 'خدمت با عکس',
            'price' => 100000,
            'duration' => 30,
            'image' => $file,
        ]);

        $service = BeautyService::where('name', 'خدمت با عکس')->first();
        $this->assertNotNull($service->image);
        Storage::disk('public')->assertExists($service->image);
    }

    public function test_store_rejects_a_negative_price(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/services/create')->post('/admin/services', [
            'name' => 'قیمت نامعتبر',
            'price' => -100,
            'duration' => 30,
        ]);

        $response->assertSessionHasErrors('price');
    }

    public function test_update_changes_service_fields(): void
    {
        $service = BeautyService::factory()->create(['name' => 'قدیمی', 'price' => 100000]);

        $response = $this->actingAs($this->admin)->put("/admin/services/{$service->id}", [
            'name' => 'جدید',
            'price' => 200000,
            'duration' => $service->duration,
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('beauty_services', ['id' => $service->id, 'name' => 'جدید', 'price' => 200000]);
    }

    public function test_update_replaces_the_old_image_and_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('services', 'public');
        $service = BeautyService::factory()->create(['image' => $oldPath]);
        $newFile = UploadedFile::fake()->image('new.jpg');

        $this->actingAs($this->admin)->put("/admin/services/{$service->id}", [
            'name' => $service->name,
            'price' => $service->price,
            'duration' => $service->duration,
            'image' => $newFile,
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, $service->fresh()->image);
    }

    public function test_destroy_removes_the_service(): void
    {
        $service = BeautyService::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/services/{$service->id}");

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseMissing('beauty_services', ['id' => $service->id]);
    }

    public function test_non_admin_cannot_access_service_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/services')->assertStatus(403);
    }
}
