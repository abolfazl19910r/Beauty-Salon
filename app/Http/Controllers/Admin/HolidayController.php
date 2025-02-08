<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index(Specialist $specialist)
    {
        $holidays = $specialist->holidays()
            ->orderBy('date')
            ->get();
        return response()->json($holidays);
    }

    public function store(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after:yesterday',
                function($attribute, $value, $fail) use ($specialist) {
                    $hasLeave = $specialist->leaves()
                        ->where('status', 'approved')
                        ->where('start_date', '<=', $value)
                        ->where('end_date', '>=', $value)
                        ->exists();

                    if ($hasLeave) {
                        $fail('در این تاریخ مرخصی ثبت شده است.');
                    }

                    $hasBooking = $specialist->bookings()
                        ->whereDate('booking_time', $value)
                        ->whereNotIn('status', ['cancelled'])
                        ->exists();

                    if ($hasBooking) {
                        $fail('در این تاریخ نوبت ثبت شده است.');
                    }
                }
            ],
            'description' => 'nullable|string|max:255'
        ]);

        $existingHoliday = $specialist->holidays()
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existingHoliday) {
            return response()->json([
                'message' => 'این تاریخ قبلاً به عنوان تعطیلی ثبت شده است.'
            ], 422);
        }

        $holiday = $specialist->holidays()->create($validated);

        return response()->json($holiday, 201);
    }

    public function destroy(Specialist $specialist, Holiday $holiday)
    {
        if ($holiday->specialist_id !== $specialist->id) {
            return response()->json([
                'message' => 'شما اجازه حذف این تعطیلی را ندارید.'
            ], 403);
        }

        if ($holiday->isPastHoliday()) {
            return response()->json([
                'message' => 'امکان حذف تعطیلی‌های گذشته وجود ندارد.'
            ], 422);
        }

        $holiday->delete();

        return response()->json([
            'message' => 'تعطیلی با موفقیت حذف شد.'
        ]);
    }

    public function upcomingHolidays(Specialist $specialist)
    {
        $holidays = $specialist->holidays()
            ->upcoming()
            ->get();

        return response()->json($holidays);
    }

    public function checkDate(Request $request, Specialist $specialist)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $isHoliday = $specialist->holidays()
            ->whereDate('date', $request->date)
            ->exists();

        return response()->json([
            'is_holiday' => $isHoliday
        ]);
    }
}
