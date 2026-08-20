<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminDashboardController/AdminDashboardAnalyticsController had no dedicated HTTP test —
 * AdminDashboardService itself was never unit-tested either. Both routes are the very
 * first page an admin sees after login (/admin/).
 *
 * ⭐ Updated (test-writing session 9): getPopularServices()/getActiveSpecialists() and
 * their routes/api/admin/dashboard.php endpoints were removed per an explicit project
 * decision (whole routes/api/admin/* group was unused React-SPA-era JSON API). getData()
 * survives via its web route (/admin/dashboard/data) instead of the removed API one.
 */
class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_dashboard_renders_the_overview(): void
    {
        Booking::factory()->count(3)->create(['payment_status' => 'paid', 'prepayment_amount' => 50000]);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['popularServices', 'topSpecialists']);
    }

    public function test_get_data_returns_summary_stats_as_json(): void
    {
        Booking::factory()->count(2)->create(['payment_status' => 'paid', 'prepayment_amount' => 100000]);

        $response = $this->actingAs($this->admin)->getJson('/admin/dashboard/data');

        $response->assertOk();
        $response->assertJsonStructure(['stats' => [
            'totalBookings', 'todayBookings', 'totalServices',
            'totalSpecialists', 'totalUsers', 'totalRevenue',
        ]]);
        $response->assertJsonPath('stats.totalBookings', 2);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }
}
