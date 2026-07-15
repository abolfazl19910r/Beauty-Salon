<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSpecialistScheduleController extends Controller
{
    public function index(Specialist $specialist)
    {
        return $this->edit($specialist);
    }

    public function edit(Specialist $specialist)
    {
        $schedules = $specialist->schedules()
            ->get()
            ->groupBy('day_of_week');

        return view('admin.specialists.schedules.edit', [
            'specialist' => $specialist,
            'schedules' => $schedules
        ]);
    }

    public function update(Request $request, Specialist $specialist)
    {
        try {
            $request->validate([
                'schedules.*.day_of_week' => 'required|integer|between:0,6',
                'schedules.*.is_active' => 'nullable',
                'schedules.*.start_time' => 'nullable|required_if:schedules.*.is_active,1',
                'schedules.*.end_time' => 'nullable|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
            ]);

            DB::beginTransaction();

            $specialist->schedules()->delete();

            if ($request->has('schedules')) {
                foreach ($request->schedules as $dayNumber => $schedule) {
                    if (isset($schedule['is_active']) && $schedule['is_active']) {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.specialists.show', $specialist)
                ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }
}
