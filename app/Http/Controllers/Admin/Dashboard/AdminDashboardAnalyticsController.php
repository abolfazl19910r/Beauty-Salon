<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

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

    public function getDailyRevenue(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(7));
            $endDate = $request->get('end_date', now());

            return response()->json([
                'dailyRevenue' => $this->dashboardService->getDailyRevenueBetween($startDate, $endDate),
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

    public function getDashboardByPeriod(string $period): JsonResponse
    {
        try {
            return response()->json($this->dashboardService->getStatsByPeriod($period));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
