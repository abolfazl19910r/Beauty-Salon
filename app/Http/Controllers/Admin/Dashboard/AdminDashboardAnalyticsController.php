<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardAnalyticsController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService)
    {
    }

    public function getData(): JsonResponse
    {
        try {
            return response()->json([
                'stats' => $this->dashboardService->getSummaryStats(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPopularServices(): JsonResponse
    {
        try {
            return response()->json([
                'popularServices' => $this->dashboardService->getPopularServicesWithTrend(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getActiveSpecialists(): JsonResponse
    {
        try {
            return response()->json([
                'activeSpecialists' => $this->dashboardService->getActiveSpecialistsWithPerformance(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
