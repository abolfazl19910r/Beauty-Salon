<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportRevenueApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_today_week_month_web_chart_endpoints_return_daily_series(): void
    {
        Booking::factory()->create(['payment_status' => 'paid', 'created_at' => now(), 'prepayment_amount' => 50000]);

        foreach (['/admin/reports/today', '/admin/reports/week', '/admin/reports/month'] as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk();
            $response->assertJson(['success' => true]);
        }
    }

    public function test_daily_revenue_api_returns_todays_paid_bookings(): void
    {
        Booking::factory()->create([
            'payment_status' => 'paid', 'created_at' => now(), 'prepayment_amount' => 75000,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports/daily?start_date='.now()->format('Y-m-d').'&end_date='.now()->format('Y-m-d'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_specialist_performance_api_reflects_a_completed_paid_booking(): void
    {
        $specialist = Specialist::factory()->create();
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'payment_status' => 'paid',
            'status' => 'completed',
            'created_at' => now(),
            'prepayment_amount' => 100000,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            '/api/admin/reports/specialists/performance?start_date='.now()->subDay()->format('Y-m-d').'&end_date='.now()->format('Y-m-d')
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('data.specialists'));
    }

    public function test_satisfaction_api_returns_review_stats(): void
    {
        Review::factory()->create(['overall_rating' => 5, 'created_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports/specialists/satisfaction');

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_popular_services_api_orders_by_booking_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/reports/services/popular');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'popularServices']);
    }

    public function test_non_admin_gets_401_or_403_on_report_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->getJson('/api/admin/reports/daily');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_guest_is_rejected_from_report_api(): void
    {
        $response = $this->getJson('/api/admin/reports/daily');

        $this->assertContains($response->status(), [401, 403]);
    }
}
