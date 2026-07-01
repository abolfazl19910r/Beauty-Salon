<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\StoreLeaveRequest;
use App\Models\Specialist;
use App\Models\SpecialistLeave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class SpecialistLeaveController extends Controller
{
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

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            abort(404, 'رکورد متخصص یافت نشد.');
        }

        $this->authorize('manageLeaves', $specialist);

        try {
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $startDateEn = str_replace($persianDigits, $englishDigits, $request->start_date_jalali);
            $endDateEn   = str_replace($persianDigits, $englishDigits, $request->end_date_jalali);

            $startDate = Jalalian::fromFormat('Y/m/d', $startDateEn)->toCarbon()->toDateString();
            $endDate   = Jalalian::fromFormat('Y/m/d', $endDateEn)->toCarbon()->toDateString();

            $specialist->leaves()->create([
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'reason'     => $request->reason,
                'status'     => 'pending',
            ]);

            return redirect()->route('specialist.leaves')
                ->with('success', 'درخواست مرخصی با موفقیت ثبت شد و در انتظار تایید است.');

        } catch (\Exception $e) {
            Log::error('خطا در ثبت مرخصی', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function destroy(SpecialistLeave $leave): RedirectResponse
    {
        $this->authorize('deleteLeave', $leave);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'فقط مرخصی‌های در انتظار تایید قابل حذف هستند.');
        }

        $leave->delete();

        return redirect()->route('specialist.leaves')
            ->with('success', 'درخواست مرخصی با موفقیت حذف شد.');
    }

    private function resolveSpecialist(): ?Specialist
    {
        $user = auth()->user();

        return Specialist::where('phone', $user->phone)->first();
    }
}
