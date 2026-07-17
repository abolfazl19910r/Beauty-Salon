<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;

/**
 * ⭐ After migration SpecialistLeave→Leave: store/update/checkAvailability logic
 * that was here before (and never had a route, dead code) has been moved to
 * App\Http\Controllers\Admin\Specialist\AdminSpecialistLeaveController +
 * App\Services\Leave\LeaveService (as per the project convention that
 * each specialist is managed with Blade, not JSON API).
 *
 * Only pendingLeaves() that already had a route (`admin.leaves.pending`) remains
 * : a global list of pending leaves, for all
 * specialists in one place. Currently returns JSON; converted to a Blade page
 * (dashboard-style) is a candidate for a separate phase, not part of this migration.
 */
class AdminLeaveController extends Controller
{
    public function pendingLeaves(): JsonResponse
    {
        $leaves = Leave::with('specialist')
            ->pending()
            ->orderBy('start_date')
            ->get();

        return response()->json($leaves);
    }
}
