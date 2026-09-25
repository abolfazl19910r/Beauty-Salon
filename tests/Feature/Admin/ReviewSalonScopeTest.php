<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، ممیزی جداسازی سالن‌ها): کل بخش «نظرات» مدیریت روی جدول reviews (بدون salon_id)
 * بی‌فیلتر کار می‌کرد — فهرست، شمارنده‌ها، میانگین، آمار ماهانه، سطل زباله، صفحه‌ی جزئیات، و حتی تایید/رد/ویژه/حذف/
 * بازگردانی/حذف دائمی نظر سالن دیگه.
 */
class ReviewSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Review $own;

    private Review $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_admin' => true]);
        $specialist = Specialist::factory()->create();
        $this->own = Review::factory()->create(['specialist_id' => $specialist->id, 'overall_rating' => 5, 'comment' => 'OWN-REVIEW-COMMENT', 'is_approved' => true]);

        app(CurrentSalon::class)->set(Salon::factory()->create(['slug' => 'other-reviews']));
        $otherSpecialist = Specialist::factory()->create();
        $this->other = Review::factory()->create(['specialist_id' => $otherSpecialist->id, 'overall_rating' => 1, 'comment' => 'OTHER-REVIEW-COMMENT', 'is_approved' => false]);
        app(CurrentSalon::class)->clear();
    }

    public function test_list_and_counters_include_only_this_salons_reviews(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.reviews.index'))->assertOk();

        $this->assertSame([$this->own->id], $response->viewData('reviews')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('totalReviews'));
        $this->assertSame(0, $response->viewData('negativeReviews'));
        $this->assertEquals(5, $response->viewData('averageRating'));
    }

    public function test_stats_include_only_this_salons_reviews(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The stats page uses HAVING on a non-aggregate query, which SQLite rejects; covered on MySQL/MariaDB.');
        }

        $response = $this->actingAs($this->owner)->get(route('admin.reviews.stats'));

        $this->assertSame(1, $response->viewData('totalReviews'));
        $this->assertCount(0, $response->viewData('recentNegativeReviews'));
    }

    public function test_trash_shows_only_this_salons_deleted_reviews(): void
    {
        $this->own->delete();
        $this->other->delete();

        $response = $this->actingAs($this->owner)->get(route('admin.reviews.trashed'))->assertOk();

        $this->assertSame([$this->own->id], $response->viewData('reviews')->pluck('id')->all());
    }

    public function test_another_salons_review_cannot_be_viewed_or_changed(): void
    {
        $this->actingAs($this->owner)->get(route('admin.reviews.show', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->post(route('admin.reviews.approve', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->post(route('admin.reviews.toggle-featured', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->delete(route('admin.reviews.destroy', $this->other))->assertNotFound();

        $fresh = Review::withoutGlobalScopes()->find($this->other->id);
        $this->assertFalse($fresh->is_approved);
        $this->assertFalse($fresh->is_featured);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_another_salons_trashed_review_cannot_be_restored_or_purged(): void
    {
        $this->other->delete();

        $this->actingAs($this->owner)->post(route('admin.reviews.restore', $this->other->id));
        $this->actingAs($this->owner)->delete(route('admin.reviews.force-delete', $this->other->id));

        $fresh = Review::withoutGlobalScopes()->withTrashed()->find($this->other->id);
        $this->assertNotNull($fresh);
        $this->assertNotNull($fresh->deleted_at);
    }

    public function test_own_review_actions_still_work(): void
    {
        $this->actingAs($this->owner)->get(route('admin.reviews.show', $this->own))->assertOk();
        $this->actingAs($this->owner)->post(route('admin.reviews.toggle-featured', $this->own))->assertRedirect();

        $this->assertTrue($this->own->fresh()->is_featured);
    }
}
