<?php

namespace Tests\Feature\User;

use App\Models\Announcement;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User\DashboardController::index() had no dedicated HTTP test before this session, despite
 * being the very first page a logged-in customer sees.
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_renders_for_an_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas([
            'popularServices',
            'userBookings',
            'upcomingBookings',
            'announcements',
            'topSpecialists',
            'recommendations',
        ]);
    }

    public function test_upcoming_bookings_excludes_cancelled_and_past_bookings(): void
    {
        $future = Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_time' => now()->addDays(2),
            'status' => 'confirmed',
        ]);
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_time' => now()->addDays(3),
            'status' => 'cancelled',
        ]);
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_time' => now()->subDays(3),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $upcoming = $response->viewData('upcomingBookings');
        $this->assertCount(1, $upcoming);
        $this->assertSame($future->id, $upcoming->first()->id);
    }

    public function test_dashboard_only_shows_the_authenticated_users_own_bookings(): void
    {
        Booking::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        Booking::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $this->assertCount(1, $response->viewData('userBookings'));
    }

    public function test_active_announcements_are_shown(): void
    {
        $active = Announcement::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);
        Announcement::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $announcements = $response->viewData('announcements');
        $this->assertCount(1, $announcements);
        $this->assertSame($active->id, $announcements->first()->id);
    }

    public function test_recommendations_fall_back_to_latest_services_with_no_booking_history(): void
    {
        BeautyService::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get('/dashboard');

        $this->assertCount(3, $response->viewData('recommendations'));
    }

    public function test_recommendations_suggest_services_in_the_same_category_not_already_booked(): void
    {
        $category = Category::factory()->create();
        $bookedService = BeautyService::factory()->create(['category_id' => $category->id]);
        $recommendedService = BeautyService::factory()->create(['category_id' => $category->id]);
        $otherCategoryService = BeautyService::factory()->create();

        Booking::factory()->create(['user_id' => $this->user->id, 'service_id' => $bookedService->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $recommendations = $response->viewData('recommendations');
        $ids = $recommendations->pluck('id');
        $this->assertTrue($ids->contains($recommendedService->id));
        $this->assertFalse($ids->contains($bookedService->id));
        $this->assertFalse($ids->contains($otherCategoryService->id));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
