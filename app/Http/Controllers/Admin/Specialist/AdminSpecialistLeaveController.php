<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Leave\StoreLeaveRequest;
use App\Http\Requests\Admin\Leave\UpdateLeaveStatusRequest;
use App\Models\Leave;
use App\Models\Specialist;
use App\Services\Leave\LeaveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminSpecialistLeaveController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
    ) {
    }

    public function index(Specialist $specialist): View
    {
        /** @var LengthAwarePaginator $leaves */
        $leaves = $specialist->leaves()->latest()->paginate(10);

        return view('admin.specialists.leaves.index', compact('specialist', 'leaves'));
    }

    /**
     * ⭐ Bug fixed: Previously this method expected start_date_jalali/end_date_jalali
     * , while the Blade leave modal would already send the Gregorian date (start_date/
     * end_date) — meaning that registering a leave from the admin panel would always fail with a
     * validation error. Now Form Request is aligned with the actual Blade data format
     * .
 */
    public function store(StoreLeaveRequest $request, Specialist $specialist): RedirectResponse
    {
        $result = $this->leaveService->store($specialist, $request->validated());

        return redirect()
            ->route('admin.specialists.leaves.index', $specialist)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function update(UpdateLeaveStatusRequest $request, Specialist $specialist, Leave $leave): RedirectResponse
    {
        if ($leave->specialist_id !== $specialist->id) {
            abort(403, 'شما اجازه ویرایش این درخواست را ندارید.');
        }

        $result = $this->leaveService->updateStatus(
            $leave,
            $request->validated('status'),
            $request->validated('reject_reason')
        );

        return redirect()
            ->route('admin.specialists.leaves.index', $specialist)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
