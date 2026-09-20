<?php

namespace App\Http\Controllers\Admin\Holiday;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Specialist;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHolidayController extends Controller
{
    public function __construct(
        protected readonly HolidayRepositoryInterface $holidayRepository,
        protected readonly LeaveRepositoryInterface $leaveRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function index(Specialist $specialist): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $holidays = $this->holidayRepository->getForSpecialist($specialist->id);

        return response()->json($holidays);
    }

    public function store(Request $request, Specialist $specialist): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after:yesterday',
                function ($attribute, $value, $fail) use ($specialist) {
                    $hasLeave = $this->leaveRepository->hasApprovedLeaveOnDate($specialist->id, $value);

                    if ($hasLeave) {
                        $fail('در این تاریخ مرخصی ثبت شده است.');
                    }

                    $hasBooking = $this->bookingRepository->hasBookingOnDate($specialist->id, $value);

                    if ($hasBooking) {
                        $fail('در این تاریخ نوبت ثبت شده است.');
                    }
                },
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $existingHoliday = $this->holidayRepository->findOnDate($specialist->id, $validated['date']);

        if ($existingHoliday) {
            return response()->json([
                'message' => 'این تاریخ قبلاً به عنوان تعطیلی ثبت شده است.',
            ], 422);
        }

        $holiday = $this->holidayRepository->createForSpecialist($specialist, $validated);

        return response()->json($holiday, 201);
    }

    public function destroy(Specialist $specialist, Holiday $holiday): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        if ($holiday->specialist_id !== $specialist->id) {
            return response()->json([
                'message' => 'شما اجازه حذف این تعطیلی را ندارید.',
            ], 403);
        }

        if ($holiday->isPastHoliday()) {
            return response()->json([
                'message' => 'امکان حذف تعطیلی‌های گذشته وجود ندارد.',
            ], 422);
        }

        $this->holidayRepository->delete($holiday);

        return response()->json([
            'message' => 'تعطیلی با موفقیت حذف شد.',
        ]);
    }

    public function upcomingHolidays(Specialist $specialist): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $holidays = $this->holidayRepository->getUpcomingForSpecialist($specialist->id);

        return response()->json($holidays);
    }

    public function checkDate(Request $request, Specialist $specialist): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $isHoliday = $this->holidayRepository->existsOnDate($specialist->id, $request->date);

        return response()->json([
            'is_holiday' => $isHoliday,
        ]);
    }
}
