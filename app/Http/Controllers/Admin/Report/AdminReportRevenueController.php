<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Http\JsonResponse;

/**
 * API endpoints related to revenue and financial reports.
 * * Derived from AdminReportsController (R-Reports).
 *
 * ⭐ Note (test-writing session 9): daily()/weekly()/monthly()/financial() used to live
 * here, reachable only via routes/api/admin/reports.php. That whole routes/api/admin/*
 * group was removed per explicit project decision (unused React-SPA-era JSON API,
 * confirmed zero consumers in resources/js or resources/views). Those four methods were
 * removed along with it; the underlying AdminReportService methods they called
 * (dailyRevenue/weeklyRevenue/monthlyRevenue/getFinancialSummary/etc.) are untouched and
 * still exercised by AdminReportsController::index() and AdminReportServiceTest.
 */
class AdminReportRevenueController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    // ── Web endpoints for Admin Dashboard chart ────────────────────
    // These methods are called from web routes (not API)
    // They work with session auth middleware — no need for auth:sanctum

    public function today(): JsonResponse
    {
        ['start' => $start, 'end' => $end] = $this->reportService->parseDateRange([
            'start_date' => today()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->dailyRevenue($start, $end),
            'meta' => ['period' => 'today'],
        ]);
    }

    public function week(): JsonResponse
    {
        ['start' => $start, 'end' => $end] = $this->reportService->parseDateRange([
            'start_date' => now()->subDays(6)->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->dailyRevenue($start, $end),
            'meta' => ['period' => 'week'],
        ]);
    }

    public function month(): JsonResponse
    {
        ['start' => $start, 'end' => $end] = $this->reportService->parseDateRange([
            'start_date' => now()->subDays(29)->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->dailyRevenue($start, $end),
            'meta' => ['period' => 'month'],
        ]);
    }
}
