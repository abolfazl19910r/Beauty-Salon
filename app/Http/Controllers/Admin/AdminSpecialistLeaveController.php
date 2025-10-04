<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\SpecialistLeave;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class AdminSpecialistLeaveController extends Controller
{
    public function index(Specialist $specialist)
    {
        $leaves = $specialist->leaves()->latest()->paginate(10);
        return view('admin.specialists.leaves.index', compact('specialist', 'leaves'));
    }

    public function store(Request $request, Specialist $specialist)
    {
        try {
            $validated = $request->validate([
                'start_date_jalali' => 'required|string',
                'end_date_jalali' => 'required|string',
                'reason' => 'nullable|string|max:255'
            ]);

            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $startDateEn = str_replace($persianDigits, $englishDigits, $validated['start_date_jalali']);
            $endDateEn = str_replace($persianDigits, $englishDigits, $validated['end_date_jalali']);

            $startDate = Jalalian::fromFormat('Y/m/d', $startDateEn)->toCarbon()->toDateString();
            $endDate = Jalalian::fromFormat('Y/m/d', $endDateEn)->toCarbon()->toDateString();

            $specialist->leaves()->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending'
            ]);

            return redirect()->route('admin.specialists.leaves.index', $specialist)
                ->with('success', 'مرخصی با موفقیت ثبت شد.');
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Specialist $specialist, SpecialistLeave $leave)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected'
            ]);

            $leave->update($validated);

            return redirect()->route('admin.specialists.leaves.index', $specialist)
                ->with('success', 'وضعیت مرخصی با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بروزرسانی اطلاعات: ' . $e->getMessage());
        }
    }
}
