<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Leave\UpdateLeaveStatusRequest;
use App\Models\Leave;
use App\Services\Leave\LeaveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLeaveController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
    ) {
    }

    /**
     * Global Leave Page — All leave requests for all specialists in one
     * * table, without having to open each specialist's page. Optional filter on
     * * status (default: all).
 */
    public function index(Request $request): View
    {
        /** @var LengthAwarePaginator $leaves */
        $leaves = Leave::with('specialist:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.leaves.index', compact('leaves'));
    }

    public function updateStatus(UpdateLeaveStatusRequest $request, Leave $leave): RedirectResponse
    {
        $result = $this->leaveService->updateStatus(
            $leave,
            $request->validated('status'),
            $request->validated('reject_reason')
        );

        return redirect()
            ->route('admin.leaves.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * JSON style endpoint (optional for future dashboard widget) — List
     * * Leave pending approval.
 */
    public function pendingLeaves(): JsonResponse
    {
        $leaves = Leave::with('specialist')
            ->pending()
            ->orderBy('start_date')
            ->get();

        return response()->json($leaves);
    }
}
