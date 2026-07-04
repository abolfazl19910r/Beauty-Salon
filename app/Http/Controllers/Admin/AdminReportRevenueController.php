<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'data'    => $this->reportService->dailyRevenue($start, $end),
            'meta'    => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'daily']],
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 84);

        return response()->json([
            'success' => true,
            'data'    => $this->reportService->weeklyRevenue($start, $end),
            'meta'    => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'weekly']],
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 365);

        return response()->json([
            'success' => true,
            'data'    => $this->reportService->monthlyRevenue($start, $end),
            'meta'    => ['period' => ['start' => $startDate, 'end' => $endDate, 'type' => 'monthly']],
        ]);
    }

    public function financial(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

        return response()->json([
            'success' => true,
            'data'    => [
                'summary'          => $this->reportService->getFinancialSummary($start, $end),
                'monthly_breakdown'=> $this->reportService->monthlyBreakdown(
                    now()->startOfYear(), now()->endOfDay()
                ),
                'service_revenue'  => $this->reportService->serviceRevenue($start, $end),
                'payment_breakdown'=> $this->reportService->paymentBreakdown(),
                'trends'           => $this->reportService->calcFinancialTrends($startDate, $endDate),
            ],
            'meta' => ['period' => ['start' => $startDate, 'end' => $endDate]],
        ]);
    }
}
