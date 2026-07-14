<?php

namespace App\Http\Controllers\Admin\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Reward;

/**
 * Loyalty program admin main page.
 * Other methods moved to separate controllers (R-AdminLoyalty phase):
 * - Reward CRUD → AdminLoyaltyRewardController
 * - Points mgmt → AdminLoyaltyPointsController
 * - Settings → AdminLoyaltySettingsController
 *
 * ⚠️ Architecture change (after SPA issues): This page is no longer a mount point for
 * React. The rewards list is fetched directly here and rendered as Blade
 * — no client-side fetch/AJAX calls.
 */
class AdminLoyaltyController extends Controller
{
    public function index()
    {
        $totalActivePoints    = LoyaltyPoint::where('type', 'earned')->sum('points');
        $totalPointUsers      = LoyaltyPoint::distinct('user_id')->count('user_id');
        $averageUserPoints    = $totalPointUsers > 0
            ? round($totalActivePoints / $totalPointUsers)
            : 0;
        $totalRedeemedRewards = Reward::sum('used_count');

        $rewards = Reward::orderBy('required_points')->get();

        return view('admin.loyalty.index', compact(
            'totalActivePoints',
            'totalPointUsers',
            'averageUserPoints',
            'totalRedeemedRewards',
            'rewards'
        ));
    }
}
