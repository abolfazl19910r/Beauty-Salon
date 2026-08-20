<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * AdminReportsController::index() was the last remaining admin controller with zero direct
 * HTTP test coverage in the whole project (its sibling API controllers —
 * AdminReportRevenueController, AdminReportSpecialistController, AdminReportExportController
 * — were already covered by AdminReportRevenueApiTest/AdminReportExportTest).
 *
 * index() unconditionally calls AdminReportService::monthlyBreakdown() regardless of the
 * selected `type` (documented bug: "AdminReportsController::index() بدون قید type، همیشه
 * monthlyBreakdown() رو صدا می‌زنه" — session 4). monthlyBreakdown() uses YEAR()/MONTH(),
 * which are MySQL-only and don't exist on SQLite (the test driver). Per this project's
 * established, deliberate policy (documented repeatedly since session 3/5) not to rewrite
 * MySQL-only production queries just to make them SQLite-testable, this test partially
 * mocks AdminReportService to stub out only monthlyBreakdown() — every other piece of
 * index() (default date-range computation, the rest of the view data) still runs for real
 * against a real database.
 */
class AdminReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->partialMock(AdminReportService::class, function ($mock) {
            $mock->shouldReceive('monthlyBreakdown')
                ->andReturn(new Collection([]));
        });
    }

    public function test_index_renders_with_no_date_range_given_defaulting_to_today(): void
    {
        Booking::factory()->create([
            'payment_status' => 'paid',
            'prepayment_amount' => 50000,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/reports');

        $response->assertOk();
        $response->assertViewHas('startDate', now()->format('Y-m-d'));
        $response->assertViewHas('endDate', now()->format('Y-m-d'));
        $response->assertViewHas('type', 'daily');
    }

    public function test_index_honors_an_explicit_date_range_and_type(): void
    {
        // Type is kept at 'daily' here — 'weekly'/'monthly' route through
        // weeklyRevenue()/monthlyRevenue(), which use YEARWEEK()/YEAR() (MySQL-only,
        // same documented SQLite gap as monthlyBreakdown() above).
        $response = $this->actingAs($this->admin)->get('/admin/reports?'.http_build_query([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'type' => 'daily',
        ]));

        $response->assertOk();
        $response->assertViewHas('startDate', '2026-01-01');
        $response->assertViewHas('endDate', '2026-01-31');
        $response->assertViewHas('type', 'daily');
    }

    public function test_index_view_data_includes_every_report_section(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports');

        $response->assertViewHasAll([
            'summary', 'revenueChart', 'popularServices', 'specialists',
            'satisfaction', 'monthlyBreakdown', 'serviceRevenue', 'paymentBreakdown',
        ]);
    }

    public function test_summary_reflects_real_paid_bookings_in_range(): void
    {
        Booking::factory()->create([
            'payment_status' => 'paid',
            'prepayment_amount' => 75000,
            'created_at' => now(),
        ]);
        Booking::factory()->create([
            'payment_status' => 'unpaid',
            'prepayment_amount' => 40000,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/reports');

        $summary = $response->viewData('summary');
        $this->assertSame(75000, (int) $summary['total_revenue']);
        $this->assertSame(2, $summary['total_bookings']);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/reports')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('login'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
