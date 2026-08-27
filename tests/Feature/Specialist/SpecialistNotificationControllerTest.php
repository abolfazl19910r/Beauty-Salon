<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SpecialistNotificationController is unusual: a specialist can receive notifications either
 * as a User (auth()->user()->notifications()) or as a Specialist (their own notifiable rows,
 * matched by phone) — every method merges both sources. These tests exercise that merge
 * explicitly, since it's the one thing that differs from every other notification controller
 * in the project.
 */
class SpecialistNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->specialist = Specialist::factory()->create();
    }

    private function user(): User
    {
        return User::where('phone', $this->specialist->phone)->firstOrFail();
    }

    public function test_index_merges_notifications_from_both_the_user_and_specialist_notifiable(): void
    {
        UserNotification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user()->id,
        ]);
        UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
        ]);

        $response = $this->actingAs($this->user())->get('/specialist/notifications');

        $response->assertOk();
        $this->assertSame(2, $response->viewData('notifications')->total());
    }

    public function test_index_shows_profile_not_found_view_when_no_specialist_matches_the_user(): void
    {
        $orphanUser = User::factory()->create();

        $response = $this->actingAs($orphanUser)->get('/specialist/notifications');

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_count_sums_unread_from_both_sources(): void
    {
        UserNotification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user()->id,
            'read_at' => null,
        ]);
        UserNotification::factory()->count(2)->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'read_at' => null,
        ]);
        // a read one shouldn't count
        UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user())->get('/specialist/notifications/count');

        $response->assertOk();
        $response->assertJson(['count' => 3]);
    }

    public function test_mark_as_read_finds_a_notification_belonging_to_the_specialist_side(): void
    {
        $notification = UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user())->post("/specialist/notifications/{$notification->id}/read");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_marks_both_sources(): void
    {
        $userNotif = UserNotification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user()->id,
            'read_at' => null,
        ]);
        $specialistNotif = UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user())->post('/specialist/notifications/mark-all-read');

        $response->assertRedirect();
        $this->assertNotNull($userNotif->fresh()->read_at);
        $this->assertNotNull($specialistNotif->fresh()->read_at);
    }

    public function test_show_and_redirect_goes_to_the_booking_show_page_when_data_has_a_booking_id(): void
    {
        $booking = \App\Models\Booking::factory()->create(['specialist_id' => $this->specialist->id]);
        $notification = UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'data' => ['booking_id' => $booking->id, 'message' => 'یک نوبت جدید'],
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user())->get("/specialist/notifications/{$notification->id}");

        $response->assertRedirect(route('specialist.bookings.show', $booking->id));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * ⭐ Fix (item 8): NewReviewReceivedNotification's payload uses 'review_id' — this branch was
     * entirely missing, so clicking a "new review" notification always fell through to the
     * dashboard fallback below instead of landing on the actual review, where the specialist can
     * respond to it.
     */
    public function test_show_and_redirect_goes_to_the_review_show_page_when_data_has_a_review_id(): void
    {
        $review = \App\Models\Review::factory()->create(['specialist_id' => $this->specialist->id]);
        $notification = UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $this->specialist->id,
            'type' => 'App\\Notifications\\Review\\NewReviewReceivedNotification',
            'data' => ['review_id' => $review->id, 'message' => 'یک نظر جدید'],
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user())->get("/specialist/notifications/{$notification->id}");

        $response->assertRedirect(route('specialist.reviews.show', $review->id));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_show_and_redirect_falls_back_to_dashboard_when_notification_is_not_found(): void
    {
        $response = $this->actingAs($this->user())->get('/specialist/notifications/'.Str::uuid());

        $response->assertRedirect(route('specialist.my-dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_latest_returns_at_most_5_merged_and_sorted_by_recency(): void
    {
        for ($i = 0; $i < 4; $i++) {
            UserNotification::factory()->create([
                'notifiable_type' => User::class,
                'notifiable_id' => $this->user()->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }
        for ($i = 0; $i < 4; $i++) {
            UserNotification::factory()->create([
                'notifiable_type' => Specialist::class,
                'notifiable_id' => $this->specialist->id,
                'created_at' => now()->subMinutes($i + 10),
            ]);
        }

        $response = $this->actingAs($this->user())->get('/specialist/notifications/latest');

        $response->assertOk();
        $this->assertCount(5, $response->json('notifications'));
    }

    public function test_a_user_without_an_associated_specialist_cannot_leak_another_specialists_notifications(): void
    {
        $otherSpecialist = Specialist::factory()->create();
        UserNotification::factory()->create([
            'notifiable_type' => Specialist::class,
            'notifiable_id' => $otherSpecialist->id,
        ]);

        $orphanUser = User::factory()->create();
        $response = $this->actingAs($orphanUser)->get('/specialist/notifications/count');

        $response->assertOk();
        $response->assertJson(['count' => 0]);
    }
}
