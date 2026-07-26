<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\Admin\Report\AdminReportService;
use Illuminate\Http\Request;

class AdminReportsController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService,
    ) {}

    public function index(Request $request)
    {
        // Previously, without start_date/end_date in the URL, the page would just show the message "No time range selected
        //" and the admin would have to manually select a
        // range before seeing any report. Now, the default of "Today" is applied (in conjunction with the
        // "Daily" button, which was already enabled by default) so that the report
        // would be visible immediately upon opening the page.
        $startDate = $request->input('start_date') ?: now()->format('Y-m-d');
        $endDate   = $request->input('end_date') ?: now()->format('Y-m-d');
        $type      = $request->input('type', 'daily');

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
            'paymentBreakdown' => $this->reportService->paymentBreakdown($start, $end),
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'type'             => $type,
        ]);
    }
}
