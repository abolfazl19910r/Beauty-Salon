<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSpecialistScheduleController extends Controller
{
    public function index($specialistId)
    {
        try {
            $specialist = Specialist::findOrFail($specialistId);
            return $this->edit($specialistId);
        } catch (\Exception $e) {
            return redirect()->route('admin.specialists.index')->with('error', 'متخصص مورد نظر یافت نشد.');
        }
    }

    public function edit($specialistId)
    {
        try {
            $specialist = Specialist::findOrFail($specialistId);

            $schedules = $specialist->schedules()
                ->get()
                ->groupBy('day_of_week');

            return view('admin.specialists.schedules.edit', [
                'specialist' => $specialist,
                'schedules' => $schedules
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.specialists.index')->with('error', 'متخصص مورد نظر یافت نشد.');
        }
    }

    public function update(Request $request, $specialistId)
    {
        try {
            if (empty($specialistId) && $request->has('specialist_id')) {
                $specialistId = $request->input('specialist_id');
            }

            $specialist = Specialist::findOrFail($specialistId);

            $request->validate([
                'schedules.*.day_of_week' => 'required|integer|between:0,6',
                'schedules.*.is_active' => 'nullable',
                'schedules.*.start_time' => 'nullable|required_with:schedules.*.is_active',
                'schedules.*.end_time' => 'nullable|required_with:schedules.*.is_active|after:schedules.*.start_time',
            ]);

            DB::beginTransaction();

            $specialist->schedules()->delete();

            if ($request->has('schedules')) {
                foreach ($request->schedules as $dayNumber => $schedule) {
                    if (isset($schedule['is_active'])) {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'is_active' => true,
                        ]);
                    } else {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => null,
                            'end_time' => null,
                            'is_active' => false,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect('/admin/specialists/' . $specialist->id)
                ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }
}
