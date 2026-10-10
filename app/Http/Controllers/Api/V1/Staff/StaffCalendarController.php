<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Exceptions\Api\ApiException;
use App\Http\Requests\Api\StaffScheduleRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\SpecialistSchedule;
use App\Services\Api\StaffBookingPresenter;
use App\Services\Leave\LeaveService;
use App\Services\Specialist\SpecialistScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * تقویم اپ همکار (بسته‌ی ۲؛ تصمیم ۲۰۲۶-۱۰-۱۰ «همه»): نمای روزبه‌روز، برنامه‌ی هفتگی (خواندن/ذخیره) و درخواست مرخصی.
 * قواعد همان وب: مرخصی «در انتظار تأیید» ثبت می‌شود و مدیر تأیید می‌کند (LeaveService)، فقط مرخصی pending حذف
 * می‌شود، برنامه‌ی هفتگی با SpecialistScheduleService (مشترک با وب). روز هفته مثل کربن: ۰ = یکشنبه.
 */
class StaffCalendarController extends StaffController
{
    protected const WEEKDAYS = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

    public function calendar(Request $request): JsonResponse
    {
        $input = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : Carbon::today();
        $to = isset($input['to']) ? Carbon::parse($input['to'])->startOfDay() : $from->copy()->addDays(6);

        if ($from->diffInDays($to) > 41) {
            throw new ApiException('range_too_large', 'بازه‌ی تقویم حداکثر ۴۲ روز است.', 422);
        }

        $specialist = $this->specialist($request);
        $schedules = SpecialistSchedule::where('specialist_id', $specialist->id)->where('is_active', true)->get()->keyBy('day_of_week');
        $holidays = Holiday::where('specialist_id', $specialist->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()
            ->keyBy(fn (Holiday $h) => $h->date->toDateString());
        $leaves = Leave::where('specialist_id', $specialist->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get();
        $bookings = Booking::where('specialist_id', $specialist->id)
            ->whereBetween('booking_time', [$from, $to->copy()->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'pending_payment'])
            ->with(['service', 'user'])->orderBy('booking_time')->get()
            ->groupBy(fn (Booking $b) => $b->booking_time->toDateString());

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $date = $day->toDateString();
            $leave = $leaves->first(fn (Leave $l) => $l->start_date->toDateString() <= $date && $l->end_date->toDateString() >= $date);
            $schedule = $schedules->get($day->dayOfWeek);

            $days[] = [
                'date' => $date,
                'date_jalali' => Jalalian::fromCarbon($day)->format('Y/m/d'),
                'weekday' => $day->dayOfWeek,
                'weekday_label' => self::WEEKDAYS[$day->dayOfWeek],
                'working_hours' => $schedule ? $this->hours($schedule) : null,
                'holiday' => ($h = $holidays->get($date)) ? ['description' => $h->description] : null,
                'leave' => $leave ? ['id' => $leave->id, 'status' => $leave->status] : null,
                'bookings' => ($bookings->get($date) ?? collect())
                    ->map(fn (Booking $b) => StaffBookingPresenter::present($b))->values(),
            ];
        }

        return ApiResponse::success($days);
    }

    public function schedule(Request $request): JsonResponse
    {
        $specialist = $this->specialist($request);
        $schedules = SpecialistSchedule::where('specialist_id', $specialist->id)->get()->keyBy('day_of_week');

        return ApiResponse::success([
            'auto_confirm_bookings' => (bool) $specialist->auto_confirm_bookings,
            'schedules' => collect(range(0, 6))->map(fn (int $d) => array_merge(
                ['day_of_week' => $d, 'weekday_label' => self::WEEKDAYS[$d]],
                ($s = $schedules->get($d)) && $s->is_active
                    ? ['is_active' => true] + $this->hours($s)
                    : ['is_active' => false, 'start_time' => null, 'end_time' => null, 'break_start' => null, 'break_end' => null],
            ))->values(),
        ]);
    }

    public function updateSchedule(StaffScheduleRequest $request, SpecialistScheduleService $service): JsonResponse
    {
        $validated = $request->validated();

        // برخلاف فرم وب، نبودنِ auto_confirm_bookings یعنی «بدون تغییر» (نه خاموش)
        $service->replace(
            $this->specialist($request),
            $validated['schedules'],
            array_key_exists('auto_confirm_bookings', $validated) ? (bool) $validated['auto_confirm_bookings'] : null,
        );

        $request->attributes->set('api_specialist', $this->specialist($request)->fresh());

        return $this->schedule($request);
    }

    public function leaves(Request $request): JsonResponse
    {
        $page = Leave::where('specialist_id', $this->specialist($request)->id)
            ->orderByDesc('start_date')->orderByDesc('id')->paginate(20);

        return ApiResponse::success(collect($page->items())->map(fn (Leave $l) => $this->leave($l))->values(), $this->pageMeta($page));
    }

    public function storeLeave(Request $request, LeaveService $leaveService): JsonResponse
    {
        $input = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $leaveService->store($this->specialist($request), $input);

        if (! $result['success']) {
            throw new ApiException('leave_conflict', $result['message'], 422);
        }

        return ApiResponse::success($this->leave($result['leave']), [], 201);
    }

    public function destroyLeave(Request $request, int $leaveId): JsonResponse
    {
        $leave = Leave::whereKey($leaveId)->where('specialist_id', $this->specialist($request)->id)->first();

        if (! $leave) {
            throw new ApiException('not_found', 'مرخصی پیدا نشد.', 404);
        }

        if ($leave->status !== 'pending') {
            throw new ApiException('leave_not_pending', 'فقط مرخصی‌های در انتظار تأیید قابل حذف هستند.', 409);
        }

        $leave->delete();

        return ApiResponse::success(null);
    }

    protected function hours(SpecialistSchedule $s): array
    {
        $t = fn ($v) => $v ? substr((string) $v, 0, 5) : null;

        return [
            'start_time' => $t($s->start_time),
            'end_time' => $t($s->end_time),
            'break_start' => $t($s->break_start),
            'break_end' => $t($s->break_end),
        ];
    }

    protected function leave(Leave $leave): array
    {
        return [
            'id' => $leave->id,
            'start_date' => $leave->start_date->toDateString(),
            'end_date' => $leave->end_date->toDateString(),
            'start_date_jalali' => Jalalian::fromCarbon($leave->start_date)->format('Y/m/d'),
            'end_date_jalali' => Jalalian::fromCarbon($leave->end_date)->format('Y/m/d'),
            'status' => $leave->status,
            'reason' => $leave->reason,
            'reject_reason' => $leave->reject_reason,
        ];
    }
}
