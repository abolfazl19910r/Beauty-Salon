<?php

namespace App\Http\Controllers\Specialist\Leave;

use App\Http\Controllers\Controller;
use App\Traits\HasJalaliDates;
use App\Traits\ResolvesSpecialist;
use App\Http\Requests\Specialist\StoreLeaveRequest;
use App\Models\Leave;
use App\Services\Leave\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SpecialistLeaveController extends Controller
{
    use HasJalaliDates;
    use ResolvesSpecialist;

    public function __construct(
        private readonly LeaveService $leaveService,
    ) {
    }

    public function index()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageLeaves', $specialist);

        $leaves = $specialist->leaves()->latest()->paginate(10);

        return view('specialist.leaves', compact('specialist', 'leaves'));
    }

    public function create()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageLeaves', $specialist);

        return view('specialist.leaves-create', compact('specialist'));
    }

    /**
     * ⭐ Now checks for conflicts with other approved leaves and previously booked
     * appointments before registering (via LeaveService shared with admin) —
     * something the previous version (SpecialistLeave) did not have at all.
     */
    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            abort(404, 'رکورد متخصص یافت نشد.');
        }

        $this->authorize('manageLeaves', $specialist);

        try {
            $startDate = $this->parseJalaliOrFail($request->start_date_jalali)->toDateString();
            $endDate   = $this->parseJalaliOrFail($request->end_date_jalali)->toDateString();

            $result = $this->leaveService->store($specialist, [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'reason'     => $request->reason,
            ]);

            return redirect()->route('specialist.leaves')
                ->with($result['success'] ? 'success' : 'error', $result['message']);

        } catch (\Exception $e) {
            Log::error('خطا در ثبت مرخصی', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطا در ذخیره اطلاعات رخ داد.');
        }
    }

    public function destroy(Leave $leave): RedirectResponse
    {
        $this->authorize('deleteLeave', $leave);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'فقط مرخصی‌های در انتظار تایید قابل حذف هستند.');
        }

        $leave->delete();

        return redirect()->route('specialist.leaves')
            ->with('success', 'درخواست مرخصی با موفقیت حذف شد.');
    }

}
