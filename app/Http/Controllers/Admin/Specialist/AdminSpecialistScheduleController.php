<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSpecialistScheduleController extends Controller
{
    public function __construct(protected readonly SpecialistScheduleRepositoryInterface $specialistScheduleRepository) {}

    public function index(Specialist $specialist): View
    {
        return $this->edit($specialist);
    }

    public function edit(Specialist $specialist): View
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $schedules = $this->specialistScheduleRepository->getGroupedBySpecialist($specialist->id);

        return view('admin.specialists.schedules.edit', [
            'specialist' => $specialist,
            'schedules' => $schedules,
        ]);
    }

    public function update(Request $request, Specialist $specialist): RedirectResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        try {
            $request->validate([
                'schedules.*.day_of_week' => 'required|integer|between:0,6',
                'schedules.*.is_active' => 'nullable',
                'schedules.*.start_time' => 'nullable|required_if:schedules.*.is_active,1',
                'schedules.*.end_time' => 'nullable|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
                'schedules.*.break_start' => 'nullable|required_with:schedules.*.break_end|after:schedules.*.start_time|before:schedules.*.end_time',
                'schedules.*.break_end' => 'nullable|required_with:schedules.*.break_start|after:schedules.*.break_start|before_or_equal:schedules.*.end_time',
            ]);

            DB::beginTransaction();

            $this->specialistScheduleRepository->replaceForSpecialist($specialist, $request->input('schedules', []));

            DB::commit();

            return redirect()->route('admin.specialists.show', $specialist)
                ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'خطا در ذخیره اطلاعات: '.$e->getMessage());
        }
    }
}
