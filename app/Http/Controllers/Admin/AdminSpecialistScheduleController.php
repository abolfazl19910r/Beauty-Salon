<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Http\Request;

class AdminSpecialistScheduleController extends Controller
{
    public function edit(Specialist $specialist)
    {
        $schedules = $specialist->schedules()->get()->groupBy('day_of_week');
        return view('admin.specialists.schedules.edit', compact('specialist', 'schedules'));
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

        return redirect()->route('admin.specialists.show', $specialist)
            ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');
    }
}
