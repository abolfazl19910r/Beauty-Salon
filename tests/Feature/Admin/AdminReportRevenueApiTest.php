<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Trimmed (test-writing session 9): this file used to also cover
 * /api/admin/reports/{daily,specialists/performance,specialists/satisfaction,services/popular}
 * — those routes (and the AdminReportSpecialistController class + the daily()/weekly()/
 * monthly()/financial() methods on AdminReportRevenueController) were removed along with
 * the whole routes/api/admin/* group per an explicit project decision (unused
 * React-SPA-era JSON API, confirmed zero consumers). The underlying AdminReportService
 * logic those methods called is untouched and still covered by AdminReportServiceTest and
 * AdminReportsControllerTest (which exercises specialistPerformance/customerSatisfaction/
 * popularServices indirectly through the real admin/reports page). Only the
 * still-live /admin/reports/{today,week,month} web-chart endpoints remain here.
 */
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

    public function test_non_admin_is_forbidden_from_the_web_chart_endpoints(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/reports/today')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/reports/today')->assertRedirect(route('login'));
    }
}

