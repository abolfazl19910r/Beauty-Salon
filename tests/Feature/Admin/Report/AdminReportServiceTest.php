<?php

namespace Tests\Feature\Admin\Report;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminReportService::class);
    }

    private function paidBooking(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'payment_status' => 'paid',
            'prepayment_amount' => 100000,
            'created_at' => now(),
        ], $overrides));
    }

    // ── parseDateRange() / validateDates() ──────────────────────────────

    public function test_parse_date_range_uses_provided_dates(): void
    {
        $result = $this->service->parseDateRange(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);

        $this->assertSame('2026-01-01', $result['startDate']);
        $this->assertSame('2026-01-31', $result['endDate']);
        $this->assertTrue($result['start']->isSameDay(\Carbon\Carbon::parse('2026-01-01')));
    }

    public function test_parse_date_range_falls_back_to_default_when_start_is_after_end(): void
    {
        $result = $this->service->parseDateRange(['start_date' => '2026-05-01', 'end_date' => '2026-01-01'], defaultSubDays: 7);

        // an invalid range (start after end) must not be silently accepted
        $this->assertNotSame('2026-05-01', $result['startDate']);
        $this->assertTrue(\Carbon\Carbon::parse($result['startDate'])->lte(\Carbon\Carbon::parse($result['endDate'])));
    }

    public function test_parse_date_range_defaults_to_today_when_no_input_given(): void
    {
        $result = $this->service->parseDateRange([], defaultSubDays: 0);

        $this->assertSame(now()->format('Y-m-d'), $result['startDate']);
        $this->assertSame(now()->format('Y-m-d'), $result['endDate']);
    }

    // ── getFinancialSummary() ────────────────────────────────────────────

    public function test_financial_summary_separates_wallet_gateway_and_admin_manual_buckets(): void
    {
        $this->paidBooking(['payment_details' => ['method' => 'wallet']]);
        $this->paidBooking(['payment_details' => ['method' => 'gateway']]);
        $this->paidBooking(['payment_details' => ['method' => 'wallet_gateway']]);
        $this->paidBooking(['payment_details' => ['method' => 'cash', 'admin_recorded' => true]]);
        // a full-discount booking must be counted in neither gateway nor wallet nor admin_manual
        $this->paidBooking(['payment_details' => ['method' => 'full_discount']]);

        $summary = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        $this->assertSame(100000.0, (float) $summary['wallet_payments']);
        $this->assertSame(200000.0, (float) $summary['gateway_payments']); // gateway + wallet_gateway
        $this->assertSame(100000.0, (float) $summary['admin_manual_payments']);
        $this->assertSame(500000.0, (float) $summary['total_revenue']);
    }

    public function test_financial_summary_only_counts_paid_bookings_for_revenue(): void
    {
        $this->paidBooking();
        Booking::factory()->create(['payment_status' => 'unpaid', 'prepayment_amount' => 999999, 'created_at' => now()]);

        $summary = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        $this->assertSame(100000.0, (float) $summary['total_revenue']);
        $this->assertSame(2, $summary['total_bookings']);
        $this->assertSame(999999.0, (float) $summary['pending_payments']);
    }

    public function test_financial_summary_is_scoped_to_the_given_date_range(): void
    {
        $this->paidBooking(['created_at' => now()->subDays(10)]); // outside range
        $inside = $this->paidBooking(['created_at' => now()]);

        $summary = $this->service->getFinancialSummary(now()->subDay(), now()->addDay());

        $this->assertSame(100000.0, (float) $summary['total_revenue']);
        $this->assertSame(1, $summary['total_bookings']);
    }

    // ── paymentBreakdown() ────────────────────────────────────────────

    public function test_payment_breakdown_percentages_sum_correctly(): void
    {
        $this->paidBooking(['payment_details' => ['method' => 'wallet']]);
        $this->paidBooking(['payment_details' => ['method' => 'wallet']]);
        $this->paidBooking(['payment_details' => ['method' => 'gateway']]);
        $this->paidBooking(['payment_details' => ['method' => 'cash', 'admin_recorded' => true]]);

        $breakdown = $this->service->paymentBreakdown(now()->subDay(), now()->addDay());

        $this->assertSame(4, $breakdown['total']);
        $this->assertSame(2, $breakdown['wallet']);
        $this->assertSame(50.0, $breakdown['wallet_percent']);
        $this->assertSame(1, $breakdown['gateway']);
        $this->assertSame(1, $breakdown['admin_manual']);
    }

    public function test_payment_breakdown_with_no_paid_bookings_returns_zeroed_structure_not_a_division_error(): void
    {
        $breakdown = $this->service->paymentBreakdown(now()->subDay(), now()->addDay());

        $this->assertSame(0, $breakdown['total']);
        $this->assertSame(0, $breakdown['gateway']);
        $this->assertArrayNotHasKey('gateway_percent', $breakdown);
    }

    // ── dailyRevenue() ───────────────────────────────────────────────

    public function test_daily_revenue_groups_by_calendar_day(): void
    {
        $this->paidBooking(['created_at' => now()->startOfDay()->addHours(2)]);
        $this->paidBooking(['created_at' => now()->startOfDay()->addHours(20)]);
        $this->paidBooking(['created_at' => now()->subDay()]);

        $rows = $this->service->dailyRevenue(now()->subDays(2), now()->addDay());

        $this->assertCount(2, $rows);
        $today = $rows->firstWhere('date', now()->format('Y-m-d'));
        $this->assertSame(200000, $today['revenue']);
        $this->assertSame(2, $today['bookings']);
    }

    public function test_daily_revenue_excludes_unpaid_bookings(): void
    {
        $this->paidBooking();
        Booking::factory()->create(['payment_status' => 'unpaid', 'prepayment_amount' => 1000000, 'created_at' => now()]);

        $rows = $this->service->dailyRevenue(now()->subDay(), now()->addDay());

        $this->assertSame(100000, $rows->first()['revenue']);
    }

    // ── specialistPerformance() / getRawBookingsForExport() commission share ──

    public function test_specialist_performance_uses_the_specialists_own_commission_rate(): void
    {
        $specialist = Specialist::factory()->create(['commission_rate' => 20]);
        $this->paidBooking(['specialist_id' => $specialist->id, 'prepayment_amount' => 100000]);

        $rows = $this->service->specialistPerformance(now()->subDay(), now()->addDay());
        $row = $rows->firstWhere('id', $specialist->id);

        $this->assertSame(20.0, $row['commission_rate']);
        $this->assertSame(80000.0, $row['specialist_share']); // 100000 * (1 - 0.20)
    }

    public function test_raw_bookings_export_computes_specialist_share_after_commission(): void
    {
        $specialist = Specialist::factory()->create(['commission_rate' => 25]);
        $booking = $this->paidBooking(['specialist_id' => $specialist->id, 'prepayment_amount' => 100000]);

        $rows = $this->service->getRawBookingsForExport(now()->subDay(), now()->addDay());
        $row = $rows->firstWhere('amount', 100000);

        $this->assertSame(75000, $row['specialist_share']);
    }

    public function test_raw_bookings_export_leaves_specialist_share_null_for_unpaid_bookings(): void
    {
        Booking::factory()->create(['payment_status' => 'unpaid', 'created_at' => now()]);

        $rows = $this->service->getRawBookingsForExport(now()->subDay(), now()->addDay());

        $this->assertNull($rows->first()['specialist_share']);
    }

    public function test_raw_bookings_export_resolves_the_real_discount_type_from_the_discount_code(): void
    {
        DiscountCode::factory()->create(['code' => 'SUMMER10', 'type' => 'percentage']);
        $this->paidBooking(['discount_code' => 'SUMMER10', 'discount_amount' => 10000]);

        $rows = $this->service->getRawBookingsForExport(now()->subDay(), now()->addDay());

        $this->assertSame('درصدی', $rows->first()['discount_type']);
    }

    public function test_raw_bookings_export_labels_admin_manual_payments_distinctly_from_the_raw_method(): void
    {
        $this->paidBooking(['payment_details' => ['method' => 'cash', 'admin_recorded' => true]]);

        $rows = $this->service->getRawBookingsForExport(now()->subDay(), now()->addDay());

        $this->assertSame('ثبت دستی ادمین', $rows->first()['payment_method']);
    }

    // ── buildExportData() (daily/default type only — weekly/monthly use MySQL-only
    //    grouping functions (YEARWEEK/YEAR/MONTH) that don't exist on SQLite, the project's
    //    designated test driver; production is MySQL-only by design so this isn't a live bug,
    //    just an SQLite testability gap for those two specific report periods) ──

    public function test_build_export_data_returns_all_expected_top_level_keys(): void
    {
        $this->paidBooking();

        $data = $this->service->buildExportData(now()->subDay(), now()->addDay(), 'daily');

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('paymentBreakdown', $data);
        $this->assertArrayHasKey('rawBookings', $data);
        $this->assertArrayHasKey('specialists', $data);
        $this->assertArrayHasKey('services', $data);
        $this->assertArrayHasKey('rows', $data);
    }

    // ── calcCompletionRate() / calcReturnRate() ─────────────────────────

    public function test_completion_rate_is_zero_for_an_empty_collection_not_a_division_error(): void
    {
        $rate = $this->service->calcCompletionRate(collect());

        $this->assertSame(0.0, $rate);
    }

    public function test_return_rate_counts_customers_with_more_than_one_booking(): void
    {
        $repeatCustomer = User::factory()->create();
        $bookings = collect([
            Booking::factory()->make(['user_id' => $repeatCustomer->id]),
            Booking::factory()->make(['user_id' => $repeatCustomer->id]),
            Booking::factory()->make(['user_id' => User::factory()->create()->id]),
        ]);

        $rate = $this->service->calcReturnRate($bookings);

        $this->assertSame(50.0, $rate); // 1 of 2 unique customers returned
    }
}
