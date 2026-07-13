<?php

namespace App\Http\Controllers\Admin\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Reward;

/**
 * صفحه‌ی اصلی مدیریت برنامه‌ی وفاداری.
 * سایر متدها به کنترلرهای مجزا منتقل شدند (فاز R-AdminLoyalty):
 *  - Reward CRUD    → AdminLoyaltyRewardController
 *  - Points mgmt   → AdminLoyaltyPointsController
 *  - Settings       → AdminLoyaltySettingsController
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

        return view('admin.loyalty.index', compact(
            'totalActivePoints',
            'totalPointUsers',
            'averageUserPoints',
            'totalRedeemedRewards'
        ));
    }
}
