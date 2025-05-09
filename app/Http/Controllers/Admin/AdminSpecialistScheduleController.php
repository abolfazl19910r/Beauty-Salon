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

    public function update(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_active' => 'boolean'
        ]);

        $specialist->schedules()->delete();

        foreach ($validated['schedules'] as $schedule) {
            $specialist->schedules()->create($schedule);
        }

        return redirect()->route('admin.specialists.show', ['specialist' => $specialist->id])
            ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');
    }
}
