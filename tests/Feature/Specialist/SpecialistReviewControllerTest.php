<?php

namespace Tests\Feature\Specialist;

use App\Models\Review;
use App\Models\Role;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SpecialistFactory only attaches the 'specialist' role to the created user if this role
        // already exists (it looks it up rather than creating it) — without it, ReviewPolicy::view
        // and ::respond (both gated on hasRole('specialist')) would always deny the specialist
        // access to their own reviews.
        Role::factory()->create(['name' => 'specialist']);
    }

    public function test_index_lists_only_the_logged_in_specialists_reviews(): void
    {
        $specialist = Specialist::factory()->create();
        $otherSpecialist = Specialist::factory()->create();
        Review::factory()->create(['specialist_id' => $specialist->id]);
        Review::factory()->create(['specialist_id' => $otherSpecialist->id]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->get('/specialist/reviews');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('reviews'));
    }

    public function test_index_filters_by_rating(): void
    {
        $specialist = Specialist::factory()->create();
        Review::factory()->create(['specialist_id' => $specialist->id, 'overall_rating' => 5]);
        Review::factory()->create(['specialist_id' => $specialist->id, 'overall_rating' => 2]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->get('/specialist/reviews?rating=5');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('reviews'));
    }

    public function test_show_is_authorized_only_for_the_owning_specialist(): void
    {
        $specialist = Specialist::factory()->create();
        $otherSpecialist = Specialist::factory()->create();
        $review = Review::factory()->create(['specialist_id' => $specialist->id]);

        $this->actingAs(User::where('phone', $specialist->phone)->first())->get("/specialist/reviews/{$review->id}")->assertOk();
        $this->actingAs(User::where('phone', $otherSpecialist->phone)->first())->get("/specialist/reviews/{$review->id}")->assertStatus(403);
    }

    public function test_respond_saves_the_specialists_response(): void
    {
        $specialist = Specialist::factory()->create();
        $review = Review::factory()->create(['specialist_id' => $specialist->id, 'specialist_response' => null]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->post("/specialist/reviews/{$review->id}/respond", [
            'response' => 'ممنون از نظر شما، خوشحالیم که رضایت داشتید.',
        ]);

        $response->assertRedirect();
        $review->refresh();
        $this->assertSame('ممنون از نظر شما، خوشحالیم که رضایت داشتید.', $review->specialist_response);
        $this->assertNotNull($review->responded_at);
    }

    public function test_respond_refuses_a_second_response_to_the_same_review(): void
    {
        $specialist = Specialist::factory()->create();
        $review = Review::factory()->create([
            'specialist_id' => $specialist->id,
            'specialist_response' => 'پاسخ قبلی',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->post("/specialist/reviews/{$review->id}/respond", [
            'response' => 'پاسخ دوم',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('پاسخ قبلی', $review->fresh()->specialist_response);
    }

    public function test_respond_is_forbidden_for_a_review_belonging_to_another_specialist(): void
    {
        $specialist = Specialist::factory()->create();
        $otherSpecialist = Specialist::factory()->create();
        $review = Review::factory()->create(['specialist_id' => $otherSpecialist->id, 'specialist_response' => null]);

        $this->actingAs(User::where('phone', $specialist->phone)->first())
            ->post("/specialist/reviews/{$review->id}/respond", ['response' => 'تلاش غیرمجاز'])
            ->assertStatus(403);
    }

    public function test_update_response_edits_an_existing_response(): void
    {
        $specialist = Specialist::factory()->create();
        $review = Review::factory()->create([
            'specialist_id' => $specialist->id,
            'specialist_response' => 'پاسخ اول',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->put("/specialist/reviews/{$review->id}/update-response", [
            'response' => 'پاسخ ویرایش‌شده',
        ]);

        $response->assertRedirect();
        $this->assertSame('پاسخ ویرایش‌شده', $review->fresh()->specialist_response);
    }

    public function test_delete_response_clears_the_response_fields(): void
    {
        $specialist = Specialist::factory()->create();
        $review = Review::factory()->create([
            'specialist_id' => $specialist->id,
            'specialist_response' => 'پاسخ',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs(User::where('phone', $specialist->phone)->first())->delete("/specialist/reviews/{$review->id}/delete-response");

        $response->assertRedirect();
        $review->refresh();
        $this->assertNull($review->specialist_response);
        $this->assertNull($review->responded_at);
    }
}
