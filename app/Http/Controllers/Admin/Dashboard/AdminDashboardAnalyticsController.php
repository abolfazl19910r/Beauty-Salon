<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardAnalyticsController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService) {}

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

    // ⭐ Note (test-writing session 9): getPopularServices()/getActiveSpecialists() used to
    // live here, reachable only via routes/api/admin/dashboard.php. That whole
    // routes/api/admin/* group was removed per explicit project decision (unused
    // React-SPA-era JSON API, confirmed zero consumers in resources/js or resources/views).
    // getData() survives because it also has a live web route
    // (admin.dashboard.data → /admin/dashboard/data, kept "as-is" per an earlier project
    // decision even though dashboard.blade.php doesn't call it either — see the comment on
    // that route). The underlying AdminDashboardService methods these two removed actions
    // called (getPopularServicesWithTrend/getActiveSpecialistsWithPerformance) are
    // untouched — they're also used directly, server-side, by
    // AdminDashboardController::dashboard().
}
