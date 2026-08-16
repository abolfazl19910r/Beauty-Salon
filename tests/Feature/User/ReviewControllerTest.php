<?php

namespace Tests\Feature\User;

use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_renders_the_review_form_for_a_valid_token(): void
    {
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);

        $response = $this->get(route('reviews.create', ['token' => $token->token]));

        $response->assertOk();
        $response->assertViewHas('booking');
    }

    public function test_create_redirects_home_without_a_token(): void
    {
        $this->get(route('reviews.create'))->assertRedirect(route('home'));
    }

    public function test_create_redirects_home_for_an_invalid_token(): void
    {
        $this->get(route('reviews.create', ['token' => 'does-not-exist']))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_create_redirects_home_for_an_expired_token(): void
    {
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);
        $token->update(['expires_at' => now()->subDay()]);

        $this->get(route('reviews.create', ['token' => $token->token]))
            ->assertRedirect(route('home'));
    }

    public function test_create_redirects_with_info_when_the_booking_was_already_reviewed(): void
    {
        $booking = Booking::factory()->create(['reviewed_at' => now()]);
        $token = ReviewToken::createForBooking($booking);

        $this->get(route('reviews.create', ['token' => $token->token]))
            ->assertRedirect(route('home'))
            ->assertSessionHas('info');
    }

    public function test_store_creates_a_review_and_consumes_the_token(): void
    {
        Notification::fake();
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);

        $response = $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 5,
            'quality_rating' => 5,
            'behavior_rating' => 5,
            'cleanliness_rating' => 5,
            'speed_rating' => 5,
            'comment' => 'عالی بود',
        ]);

        $response->assertRedirect(route('reviews.thank-you'));
        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'overall_rating' => 5,
            'comment' => 'عالی بود',
        ]);
        $this->assertNotNull($booking->fresh()->reviewed_at);
        $this->assertTrue($token->fresh()->is_used);
    }

    public function test_store_awards_loyalty_points_to_the_booking_owner(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $token = ReviewToken::createForBooking($booking);

        $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 4,
            'quality_rating' => 4,
            'behavior_rating' => 4,
            'cleanliness_rating' => 4,
            'speed_rating' => 4,
        ]);

        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => 10,
        ]);
    }

    public function test_store_notifies_admins_for_a_negative_review(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);

        $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 1,
            'quality_rating' => 1,
            'behavior_rating' => 1,
            'cleanliness_rating' => 1,
            'speed_rating' => 1,
            'comment' => 'بد بود',
        ]);

        Notification::assertSentTo($admin, \App\Notifications\Review\NegativeReviewNotification::class);
    }

    public function test_store_does_not_notify_admins_for_a_positive_review(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);

        $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 5,
            'quality_rating' => 5,
            'behavior_rating' => 5,
            'cleanliness_rating' => 5,
            'speed_rating' => 5,
        ]);

        Notification::assertNotSentTo($admin, \App\Notifications\Review\NegativeReviewNotification::class);
    }

    public function test_store_with_an_invalid_token_fails_gracefully_without_a_review(): void
    {
        $response = $this->post(route('reviews.store'), [
            'token' => 'garbage-token',
            'overall_rating' => 5,
            'quality_rating' => 5,
            'behavior_rating' => 5,
            'cleanliness_rating' => 5,
            'speed_rating' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_store_rejects_a_rating_outside_the_one_to_five_range(): void
    {
        $booking = Booking::factory()->create();
        $token = ReviewToken::createForBooking($booking);

        $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 6,
            'quality_rating' => 5,
            'behavior_rating' => 5,
            'cleanliness_rating' => 5,
            'speed_rating' => 5,
        ])->assertSessionHasErrors('overall_rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_store_redirects_with_info_when_the_booking_was_already_reviewed(): void
    {
        $booking = Booking::factory()->create(['reviewed_at' => now()]);
        $token = ReviewToken::createForBooking($booking);

        $response = $this->post(route('reviews.store'), [
            'token' => $token->token,
            'overall_rating' => 5,
            'quality_rating' => 5,
            'behavior_rating' => 5,
            'cleanliness_rating' => 5,
            'speed_rating' => 5,
        ]);

        $response->assertRedirect(route('reviews.thank-you'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_specialist_reviews_lists_only_approved_reviews(): void
    {
        $specialist = \App\Models\Specialist::factory()->create();
        $approved = Review::factory()->create(['specialist_id' => $specialist->id, 'is_approved' => true]);
        Review::factory()->create(['specialist_id' => $specialist->id, 'is_approved' => false]);

        $response = $this->get(route('reviews.specialist', $specialist->id));

        $response->assertOk();
        $reviews = $response->viewData('reviews');
        $this->assertCount(1, $reviews);
        $this->assertSame($approved->id, $reviews->first()->id);
    }
}
