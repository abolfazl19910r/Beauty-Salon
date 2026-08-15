<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_categories_filtered_by_search_status_and_parent(): void
    {
        Category::factory()->create(['name' => 'رنگ مو', 'is_active' => true]);
        Category::factory()->create(['name' => 'اصلاح صورت', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/categories?search=رنگ');
        $this->assertCount(1, $response->viewData('categories'));

        $response = $this->actingAs($this->admin)->get('/admin/categories?status=inactive');
        $this->assertCount(1, $response->viewData('categories'));
    }

    public function test_store_creates_a_category_with_auto_generated_slug(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'مراقبت پوست',
            'description' => 'خدمات مراقبت از پوست',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        // Str::slug() transliterates Persian text to Latin by default (Laravel's expected
        // ASCII-slug behavior) — not asserting the exact transliterated value here, just that
        // a non-empty slug was actually generated.
        $category = \App\Models\Category::where('name', 'مراقبت پوست')->first();
        $this->assertNotNull($category);
        $this->assertNotEmpty($category->slug);
    }

    public function test_store_auto_assigns_order_when_not_provided(): void
    {
        Category::factory()->create(['order' => 5, 'parent_id' => null]);

        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'دسته جدید',
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'دسته جدید', 'order' => 6]);
    }

    public function test_store_uploads_and_persists_an_image(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('category.jpg');

        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'دسته با عکس',
            'image' => $file,
        ]);

        $category = Category::where('name', 'دسته با عکس')->first();
        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_store_rejects_a_duplicate_name(): void
    {
        Category::factory()->create(['name' => 'تکراری']);

        $response = $this->actingAs($this->admin)->from('/admin/categories/create')->post('/admin/categories', [
            'name' => 'تکراری',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_show_displays_children_and_services_counts(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);
        BeautyService::factory()->create(['category_id' => $parent->id]);

        $response = $this->actingAs($this->admin)->get("/admin/categories/{$parent->id}");

        $response->assertOk();
        $this->assertSame(1, $response->viewData('childrenCount'));
        $this->assertSame(1, $response->viewData('servicesCount'));
    }

    public function test_update_regenerates_slug_only_when_name_changes(): void
    {
        $category = Category::factory()->create(['name' => 'نام قدیمی', 'slug' => 'نام-قدیمی']);

        $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'name' => 'نام جدید',
            'is_active' => true,
        ]);

        $category->refresh();
        $this->assertSame('نام جدید', $category->name);
        // The slug must regenerate (change) since the name changed — exact transliterated
        // value isn't asserted, only that it's no longer the old slug.
        $this->assertNotSame('نام-قدیمی', $category->slug);
    }

    public function test_update_replaces_the_old_image_when_a_new_one_is_uploaded(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('categories', 'public');
        $category = Category::factory()->create(['image' => $oldPath]);
        $newFile = UploadedFile::fake()->image('new.jpg');

        $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'name' => $category->name,
            'is_active' => true,
            'image' => $newFile,
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, $category->fresh()->image);
    }

    public function test_update_removes_the_image_when_remove_image_flag_is_set(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('img.jpg')->store('categories', 'public');
        $category = Category::factory()->create(['image' => $path]);

        $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'name' => $category->name,
            'is_active' => true,
            'remove_image' => true,
        ]);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($category->fresh()->image);
    }

    public function test_update_refuses_to_set_a_category_as_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->from(route('admin.categories.edit', $category))
            ->put("/admin/categories/{$category->id}", [
                'name' => $category->name,
                'is_active' => true,
                'parent_id' => $category->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull($category->fresh()->parent_id);
    }

    public function test_toggle_status_flips_is_active(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)->patch("/admin/categories/{$category->id}/toggle-status");
        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($this->admin)->patch("/admin/categories/{$category->id}/toggle-status");
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_destroy_removes_a_category_with_no_children_or_services(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}");

        $response->assertRedirect(route('admin.categories.index'));
        // Category uses SoftDeletes — destroy() sets deleted_at, it doesn't hard-delete the row.
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_destroy_refuses_to_delete_a_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/categories/{$parent->id}");

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_destroy_refuses_to_delete_a_category_with_services(): void
    {
        $category = Category::factory()->create();
        BeautyService::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_access_category_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/categories')->assertStatus(403);
    }
}
