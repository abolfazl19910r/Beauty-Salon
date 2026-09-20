<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Leave\UpdateLeaveStatusRequest;
use App\Models\Leave;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Leave\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLeaveController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly LeaveRepositoryInterface $leaveRepository,
        private readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
        ];

        $leaves = $this->leaveRepository->paginateWithFilters($filters, 15);

        return view('admin.leaves.index', compact('leaves'));
    }

    public function updateStatus(UpdateLeaveStatusRequest $request, Leave $leave): RedirectResponse
    {
        $this->ensureSalonOwnership(
            $this->specialistRepository->getSalonIdIgnoringScopes($leave->specialist_id)
        );

        $result = $this->leaveService->updateStatus(
            $leave,
            $request->validated('status'),
            $request->validated('reject_reason')
        );

        return redirect()
            ->route('admin.leaves.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function pendingLeaves(): JsonResponse
    {
        $leaves = $this->leaveRepository->getPending();

        return response()->json($leaves);
    }
}
