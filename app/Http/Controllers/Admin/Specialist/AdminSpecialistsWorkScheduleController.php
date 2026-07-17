<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Specialist\UpdateWorkScheduleRequest;
use App\Models\Specialist;
use App\Services\Specialist\WorkScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSpecialistsWorkScheduleController extends Controller
{
    public function __construct(
        private readonly WorkScheduleService $workScheduleService,
    ) {
    }

    /**
     * Display the Specialist WorkSchedule for the admin.
     *
     * Previously, this method would incorrectly display the old system data (SpecialistSchedule, separate days
     * ), not the actual WorkSchedule record that it is responsible for.
 */
    public function index(Specialist $specialist): View
    {
        $schedule = $specialist->workSchedule;

        return view('admin.specialists.schedules.work-edit', compact('specialist', 'schedule'));
    }

    public function store(UpdateWorkScheduleRequest $request, Specialist $specialist): RedirectResponse
    {
        $this->workScheduleService->upsert($specialist, $request->validated());

        return redirect()
            ->route('admin.specialists.work-schedule.index', $specialist)
            ->with('success', 'برنامه کاری با موفقیت ذخیره شد.');
    }

    public function update(UpdateWorkScheduleRequest $request, Specialist $specialist): RedirectResponse
    {
        $this->workScheduleService->upsert($specialist, $request->validated());

        return redirect()
            ->route('admin.specialists.work-schedule.index', $specialist)
            ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');
    }

    public function destroy(Specialist $specialist): RedirectResponse
    {
        $this->workScheduleService->delete($specialist);

        return redirect()
            ->route('admin.specialists.work-schedule.index', $specialist)
            ->with('success', 'برنامه کاری حذف شد.');
    }

    public function checkAvailability(Request $request, Specialist $specialist): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);

        $schedule = $specialist->workSchedule;

        if (!$schedule || !$schedule->is_active) {
            return response()->json([
                'available' => false,
                'reason'    => 'برنامه کاری تعریف نشده است',
            ]);
        }

        if (!$schedule->isWorkingDay($request->date)) {
            return response()->json(['available' => false, 'reason' => 'روز غیرکاری']);
        }

        if (!$schedule->isWorkingTime($request->time)) {
            return response()->json(['available' => false, 'reason' => 'خارج از ساعت کاری']);
        }

        $hasHoliday = $specialist->holidays()->whereDate('date', $request->date)->exists();
        if ($hasHoliday) {
            return response()->json(['available' => false, 'reason' => 'روز تعطیل']);
        }

        $hasLeave = $specialist->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $request->date)
            ->whereDate('end_date', '>=', $request->date)
            ->exists();

        if ($hasLeave) {
            return response()->json(['available' => false, 'reason' => 'مرخصی']);
        }

        $hasBooking = $specialist->bookings()
            ->whereDate('booking_time', $request->date)
            ->whereTime('booking_time', $request->time)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasBooking) {
            return response()->json(['available' => false, 'reason' => 'رزرو شده']);
        }

        return response()->json(['available' => true]);
    }

    public function getAvailableSlots(Request $request, Specialist $specialist): JsonResponse
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);

        $schedule = $specialist->workSchedule;
        if (!$schedule || !$schedule->is_active) {
            return response()->json([]);
        }

        $slots = $schedule->getAvailableTimeSlots($request->date);

        $bookedSlots = $specialist->bookings()
            ->whereDate('booking_time', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('booking_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        return response()->json(array_values(array_diff($slots, $bookedSlots)));
    }
}
