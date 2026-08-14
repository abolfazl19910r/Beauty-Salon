<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_reviews_with_summary_counts(): void
    {
        Review::factory()->create(['overall_rating' => 5, 'is_approved' => true]);
        Review::factory()->create(['overall_rating' => 1, 'is_approved' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/reviews');

        $response->assertOk();
        $this->assertSame(2, $response->viewData('totalReviews'));
        $this->assertSame(1, $response->viewData('approvedReviews'));
        $this->assertSame(1, $response->viewData('negativeReviews'));
    }

    public function test_index_filters_by_rating_and_negative_scope(): void
    {
        Review::factory()->create(['overall_rating' => 5]);
        Review::factory()->create(['overall_rating' => 2]);

        $response = $this->actingAs($this->admin)->get('/admin/reviews?rating=5');
        $this->assertCount(1, $response->viewData('reviews'));

        $response = $this->actingAs($this->admin)->get('/admin/reviews?negative=1');
        $this->assertCount(1, $response->viewData('reviews'));
    }

    public function test_show_displays_a_single_review_with_relations_loaded(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/reviews/{$review->id}");

        $response->assertOk();
        $this->assertTrue($response->viewData('review')->relationLoaded('user'));
    }

    public function test_approve_sets_is_approved_true(): void
    {
        $review = Review::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/approve");

        $response->assertRedirect();
        $this->assertTrue($review->fresh()->is_approved);
    }

    public function test_reject_sets_is_approved_false(): void
    {
        $review = Review::factory()->create(['is_approved' => true]);

        $response = $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/reject");

        $response->assertRedirect();
        $this->assertFalse($review->fresh()->is_approved);
    }

    public function test_toggle_featured_flips_the_flag(): void
    {
        $review = Review::factory()->create(['is_featured' => false]);

        $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/toggle-featured");
        $this->assertTrue($review->fresh()->is_featured);

        $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/toggle-featured");
        $this->assertFalse($review->fresh()->is_featured);
    }

    public function test_destroy_soft_deletes_the_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    public function test_trashed_lists_only_soft_deleted_reviews(): void
    {
        $active = Review::factory()->create();
        $deleted = Review::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->admin)->get('/admin/reviews/trashed');

        $response->assertOk();
        $ids = $response->viewData('reviews')->pluck('id');
        $this->assertTrue($ids->contains($deleted->id));
        $this->assertFalse($ids->contains($active->id));
    }

    public function test_restore_brings_back_a_soft_deleted_review(): void
    {
        $review = Review::factory()->create();
        $review->delete();

        $response = $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/restore");

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'deleted_at' => null]);
    }

    public function test_force_delete_permanently_removes_a_review(): void
    {
        $review = Review::factory()->create();
        $review->delete();

        $response = $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}/force-delete");

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_non_admin_cannot_access_review_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/reviews')->assertStatus(403);
    }
}
