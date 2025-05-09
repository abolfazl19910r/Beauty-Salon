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

    }
}
