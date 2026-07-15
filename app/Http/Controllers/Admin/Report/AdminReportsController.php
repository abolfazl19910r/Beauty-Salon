<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\Request;

class AdminReportsController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $type      = $request->input('type', 'daily');

        if (! $startDate || ! $endDate) {
            return view('admin.reports.index', [
                'startDate'        => null,
                'endDate'          => null,
                'type'             => $type,
                'summary'          => [],
                'revenueChart'     => [],
                'popularServices'  => collect(),
                'specialists'      => collect(),
                'satisfaction'     => collect(),
                'monthlyBreakdown' => collect(),
                'serviceRevenue'   => collect(),
            ]);
        }

        ['start' => $start, 'end' => $end] = $this->reportService->parseDateRange(
            ['start_date' => $startDate, 'end_date' => $endDate]
        );

        return view('admin.reports.index', [
            'summary'          => $this->reportService->getSummary($start, $end),
            'revenueChart'     => $this->reportService->getRevenueChartData($start, $end, $type),
            'popularServices'  => $this->reportService->getPopularServicesForIndex($start, $end),
            'specialists'      => $this->reportService->getSpecialistPerformanceForIndex($start, $end),
            'satisfaction'     => $this->reportService->customerSatisfaction($start, $end),
            'monthlyBreakdown' => $this->reportService->monthlyBreakdown($start, $end),
            'serviceRevenue'   => $this->reportService->serviceRevenue($start, $end),
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'type'             => $type,
        ]);
    }
}
