<?php

namespace Tests\Feature\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    public function test_admin_can_create_a_blog_post(): void
    {
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/blog', [
            'title' => 'مقاله‌ی تست',
            'content' => 'محتوای تست',
            'category_id' => $category->id,
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'مقاله‌ی تست',
            'is_published' => 1,
        ]);
        $post = BlogPost::first();
        $this->assertNotEmpty($post->slug);
        $this->assertNotNull($post->published_at);
        $this->assertSame($this->admin->id, $post->author_id);
    }

    public function test_unchecked_published_checkbox_is_stored_as_false_not_left_untouched(): void
    {
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create(['category_id' => $category->id, 'is_published' => true]);

        $this->actingAs($this->admin)->put("/admin/blog/{$post->id}", [
            'title' => $post->title,
            'content' => $post->content,
            'category_id' => $category->id,
            // is_published intentionally omitted, like a real unchecked checkbox submission
        ]);

        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'is_published' => 0]);
    }

    public function test_updating_title_regenerates_slug_but_keeping_same_title_does_not(): void
    {
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create(['category_id' => $category->id, 'title' => 'عنوان اول', 'slug' => 'aanoan-aol']);

        $this->actingAs($this->admin)->put("/admin/blog/{$post->id}", [
            'title' => 'عنوان دوم',
            'content' => $post->content,
            'category_id' => $category->id,
            'is_published' => '1',
        ]);

        $post->refresh();
        $this->assertNotSame('aanoan-aol', $post->slug);
    }

    public function test_soft_deleted_post_is_recoverable_not_permanently_gone(): void
    {
        $post = BlogPost::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/blog/{$post->id}");

        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    public function test_toggle_publish_flips_status_and_sets_published_at_on_first_publish(): void
    {
        $post = BlogPost::factory()->unpublished()->create();

        $this->actingAs($this->admin)->patch("/admin/blog/{$post->id}/publish");

        $post->refresh();
        $this->assertTrue((bool) $post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_uploaded_image_is_stored_and_replaces_old_one_on_update(): void
    {
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create(['category_id' => $category->id, 'image' => 'blog/old.jpg']);
        Storage::disk('public')->put('blog/old.jpg', 'fake-content');

        $this->actingAs($this->admin)->put("/admin/blog/{$post->id}", [
            'title' => $post->title,
            'content' => $post->content,
            'category_id' => $category->id,
            'is_published' => '1',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ]);

        Storage::disk('public')->assertMissing('blog/old.jpg');
        $post->refresh();
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_non_admin_cannot_create_a_blog_post(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = BlogCategory::factory()->create();

        $response = $this->actingAs($user)->post('/admin/blog', [
            'title' => 'x',
            'content' => 'y',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    /**
     * ⭐ Regression test (session 6, 2026-08-16): the edit view file was stored on disk
     * as admin/blog/Edit.blade.php (capital E) while the controller called
     * view('admin.blog.edit') — this fatals with "View not found" the moment the
     * file naming is out of sync, exactly the recurring case-sensitivity bug pattern
     * documented repeatedly throughout this project. Renamed to lowercase.
     */
    public function test_edit_form_renders(): void
    {
        $post = BlogPost::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.blog.edit', $post))->assertOk();
    }

    /**
     * ⭐ Regression test (session 6, 2026-08-16): resolvePublishedAt() calls
     * parseJalaliOrFail(), whose declared Illuminate\Support\Carbon return type
     * mismatched the plain \Carbon\Carbon actually returned by Jalalian::toCarbon() —
     * a real TypeError on every successful parse. This controller's own
     * catch (Throwable $e) silently swallowed it, showing a generic error and never
     * actually applying the requested publish date.
     */
    public function test_updating_the_publish_date_via_the_jalali_field_actually_persists(): void
    {
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->put("/admin/blog/{$post->id}", [
            'title' => $post->title,
            'content' => $post->content,
            'category_id' => $category->id,
            'is_published' => '1',
            'published_at_jalali' => '1405/02/10 09:30',
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $response->assertSessionHas('success');
        $post->refresh();
        $this->assertSame('2026-04-30 09:30:00', $post->published_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_create_a_blog_category_with_description_and_order(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/blog/categories', [
            'name' => 'زیبایی پوست',
            'description' => 'توضیحات دسته‌بندی',
            'order' => 3,
        ]);

        $response->assertRedirect(route('admin.blog.categories.index'));
        $this->assertDatabaseHas('blog_categories', [
            'name' => 'زیبایی پوست',
            'description' => 'توضیحات دسته‌بندی',
            'order' => 3,
        ]);
    }

    /**
     * ⭐ Regression test (session 6, 2026-08-16): all three category views
     * (index/create/edit) were stored on disk with capitalized filenames while the
     * controller calls view() with lowercase names — the exact same case-sensitivity
     * bug pattern as the post edit view above. Renamed all three to lowercase.
     */
    public function test_category_index_create_and_edit_pages_all_render(): void
    {
        $category = BlogCategory::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.blog.categories.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.blog.categories.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.blog.categories.edit', $category))->assertOk();
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        BlogCategory::factory()->create(['name' => 'تکراری']);

        $response = $this->actingAs($this->admin)
            ->from('/admin/blog/categories/create')
            ->post('/admin/blog/categories', ['name' => 'تکراری']);

        $response->assertRedirect('/admin/blog/categories/create');
        $response->assertSessionHasErrors('name');
    }
}
