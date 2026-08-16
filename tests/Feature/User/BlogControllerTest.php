<?php

namespace Tests\Feature\User;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ First-ever test coverage for the public blog controller. Also serves as a
 * regression test for the case-sensitivity bug found in session 6 (2026-08-16):
 * blog/Index.blade.php and blog/Show.blade.php were stored on disk with capitalized
 * filenames while the controller calls view('blog.index')/view('blog.show') in
 * lowercase — a fatal "View not found" on any case-sensitive filesystem. Renamed to
 * lowercase.
 */
class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts_that_are_past_their_publish_date(): void
    {
        $category = BlogCategory::factory()->create();
        $published = BlogPost::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        BlogPost::factory()->unpublished()->create(['category_id' => $category->id]);
        BlogPost::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(1, $posts);
        $this->assertSame($published->id, $posts->first()->id);
    }

    public function test_index_filters_by_category(): void
    {
        $categoryA = BlogCategory::factory()->create();
        $categoryB = BlogCategory::factory()->create();
        $postA = BlogPost::factory()->create([
            'category_id' => $categoryA->id, 'is_published' => true, 'published_at' => now()->subDay(),
        ]);
        BlogPost::factory()->create([
            'category_id' => $categoryB->id, 'is_published' => true, 'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index', ['category' => $categoryA->id]));

        $posts = $response->viewData('posts');
        $this->assertCount(1, $posts);
        $this->assertSame($postA->id, $posts->first()->id);
    }

    public function test_show_renders_a_published_post_and_increments_views(): void
    {
        $post = BlogPost::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'views' => 5,
        ]);

        $response = $this->get(route('blog.show', $post));

        $response->assertOk();
        $this->assertSame(6, $post->fresh()->views);
    }

    public function test_show_returns_404_for_an_unpublished_post(): void
    {
        $post = BlogPost::factory()->unpublished()->create();

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_show_returns_404_for_a_post_scheduled_in_the_future(): void
    {
        $post = BlogPost::factory()->create([
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_show_includes_up_to_three_related_posts_from_the_same_category(): void
    {
        $category = BlogCategory::factory()->create();
        $post = BlogPost::factory()->create([
            'category_id' => $category->id, 'is_published' => true, 'published_at' => now()->subDay(),
        ]);
        BlogPost::factory()->count(4)->create([
            'category_id' => $category->id, 'is_published' => true, 'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.show', $post));

        $this->assertCount(3, $response->viewData('relatedPosts'));
    }
}
