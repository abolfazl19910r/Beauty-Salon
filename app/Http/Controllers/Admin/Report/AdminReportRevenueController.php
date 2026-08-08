<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints related to revenue and financial reports.
 * * Derived from AdminReportsController (R-Reports).
 */
class AdminReportRevenueController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function daily(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

        return response()->json([
            'success' => true,
            'data' => $this->reportService->dailyRevenue($start, $end),
            'meta' => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'daily']],
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 84);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->weeklyRevenue($start, $end),
            'meta' => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'weekly']],
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 365);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->monthlyRevenue($start, $end),
            'meta' => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'monthly']],
        ]);
    }

    public function financial(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->reportService->getFinancialSummary($start, $end),
                'monthly_breakdown' => $this->reportService->monthlyBreakdown(
                    now()->startOfYear(), now()->endOfDay()
                ),
                'service_revenue' => $this->reportService->serviceRevenue($start, $end),
                'payment_breakdown' => $this->reportService->paymentBreakdown($start, $end),
                'trends' => $this->reportService->calcFinancialTrends($startDate, $endDate),
            ],
            'meta' => ['period' => ['start' => $startDate, 'end' => $endDate]],
        ]);
    }

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
