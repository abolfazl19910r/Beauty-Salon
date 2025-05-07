<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminWorkScheduleController extends Controller
{
    public function index(Specialist $specialist)
    {
        $schedules = $specialist->schedules()->get()->groupBy('day_of_week');
        return view('admin.schedule.index', compact('specialist', 'schedules'));
    }

    public function store(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'work_days' => 'required|array',
            'work_days.*' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $hasConflict = $specialist->bookings()
            ->whereDate('booking_time', $request->date)
            ->exists();

        if ($hasConflict) {
            return response()->json(['message' => 'در این تاریخ نوبت ثبت شده وجود دارد'], 422);
        }

        $schedule = WorkSchedule::updateOrCreate(
            ['specialist_id' => $specialist->id],
            $validated
        );

        return response()->json($schedule);
    }

    public function checkAvailability(Request $request, Specialist $specialist)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i'
        ]);

        $dateTime = Carbon::parse("{$request->date} {$request->time}");
        $schedule = $specialist->workSchedule;

        if (!$schedule || !$schedule->is_active) {
            return response()->json([
                'available' => false,
                'reason' => 'برنامه کاری تعریف نشده است'
            ]);
        }

        if (!$schedule->isWorkingDay($request->date)) {
            return response()->json([
                'available' => false,
                'reason' => 'روز غیرکاری'
            ]);
        }

        if (!$schedule->isWorkingTime($request->time)) {
            return response()->json([
                'available' => false,
                'reason' => 'خارج از ساعت کاری'
            ]);
        }

        $hasHoliday = $specialist->holidays()
            ->whereDate('date', $request->date)
            ->exists();

        if ($hasHoliday) {
            return response()->json([
                'available' => false,
                'reason' => 'روز تعطیل'
            ]);
        }

        $hasLeave = $specialist->leaves()
            ->where('status', 'approved')
            ->where(function($query) use ($request) {
                $query->where(function($q) use ($request) {
                    $q->whereDate('start_date', '<=', $request->date)
                        ->whereDate('end_date', '>=', $request->date);
                });
            })
            ->exists();

        if ($hasLeave) {
            return response()->json([
                'available' => false,
                'reason' => 'مرخصی'
            ]);
        }

        $hasBooking = $specialist->bookings()
            ->whereDate('booking_time', $request->date)
            ->whereTime('booking_time', $request->time)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasBooking) {
            return response()->json([
                'available' => false,
                'reason' => 'رزرو شده'
            ]);
        }

        return response()->json(['available' => true]);
    }

    public function getAvailableSlots(Request $request, Specialist $specialist)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $schedule = $specialist->workSchedule;
        if (!$schedule || !$schedule->is_active) {
            return response()->json([]);
        }

        $slots = $schedule->getAvailableTimeSlots($request->date);

        $bookedSlots = $specialist->bookings()
            ->whereDate('booking_time', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('booking_time')
            ->map(function($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        $availableSlots = array_diff($slots, $bookedSlots);

        return response()->json(array_values($availableSlots));
    }
}
