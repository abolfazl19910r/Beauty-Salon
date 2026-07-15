<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportSpecialistController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function performance(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'));

        return response()->json([
            'success' => true,
            'data'    => ['specialists' => $this->reportService->specialistPerformance($start, $end)],
            'meta'    => ['period' => ['start' => $startDate, 'end' => $endDate]],
        ]);
    }

    public function satisfaction(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end, 'startDate' => $startDate, 'endDate' => $endDate]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 90);

        return response()->json([
            'success' => true,
            'data'    => ['satisfaction' => $this->reportService->customerSatisfaction($start, $end)],
            'meta'    => ['period' => ['start' => $startDate, 'end' => $endDate]],
        ]);
    }

    public function popularServices(Request $request): JsonResponse
    {
        ['start' => $start, 'end' => $end]
            = $this->reportService->parseDateRange($request->only('start_date', 'end_date'), defaultSubDays: 90);

        return response()->json([
            'success'        => true,
            'popularServices'=> $this->reportService->popularServices($start, $end, limit: 5),
        ]);
    }
}
