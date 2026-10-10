<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Services\Api\StaffBookingPresenter;
use App\Services\Specialist\SpecialistBookingActions;
use App\Services\Specialist\SpecialistDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * نوبت‌های متخصص در اپ «ماهرو همکار» (بسته‌ی ۲): امروز، فهرست، جزئیات، پذیرش، لغو/رد، انجام شد.
 * نوبت متخصص دیگر (حتی هم‌سالن) ۴۰۴ است، نه ۴۰۳، تا وجود شناسه تأیید نشود.
 */
class StaffBookingController extends StaffController
{
    public function __construct(
        protected readonly SpecialistBookingActions $actions,
        protected readonly SpecialistDashboardService $dashboard,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $specialist = $this->specialist($request);
        $bookings = $this->dashboard->getTodaySchedule($specialist);

        return ApiResponse::success([
            'date' => Carbon::today()->toDateString(),
            'date_jalali' => Jalalian::fromCarbon(Carbon::today())->format('Y/m/d'),
            'date_label' => Jalalian::fromCarbon(Carbon::today())->format('l، j F Y'),
            'summary' => [
                'count' => $bookings->count(),
                'pending_count' => $bookings->where('status', 'pending')->count(),
                'revenue' => $this->dashboard->getTodayRevenue($specialist),
            ],
            'bookings' => $bookings->map(fn (Booking $b) => StaffBookingPresenter::present($b))->values(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $input = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(StaffBookingPresenter::STATUS_LABELS))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : Carbon::today();
        $to = isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : $from->copy()->addDays(30)->endOfDay();

        if ($from->diffInDays($to) > 92) {
            throw new ApiException('range_too_large', 'بازه‌ی تاریخ حداکثر ۹۲ روز است.', 422);
        }

        $query = Booking::query()
            ->where('specialist_id', $this->specialist($request)->id)
            ->whereBetween('booking_time', [$from, $to])
            ->with(['service', 'user'])
            ->orderBy('booking_time');

        // بدون فیلتر، «در درگاه» (pending_payment) نمی‌آید — مثل امروز
        isset($input['status'])
            ? $query->where('status', $input['status'])
            : $query->where('status', '!=', 'pending_payment');

        $page = $query->paginate((int) ($input['per_page'] ?? 20));

        return ApiResponse::success(
            collect($page->items())->map(fn (Booking $b) => StaffBookingPresenter::present($b))->values(),
            $this->pageMeta($page) + ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        );
    }

    public function show(Request $request, int $bookingId): JsonResponse
    {
        return ApiResponse::success(StaffBookingPresenter::present($this->ownBooking($request, $bookingId)));
    }

    public function confirm(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->ownBooking($request, $bookingId);
        $changed = $this->actions->confirm($booking);

        return $this->afterAction($booking, $changed);
    }

    public function cancel(Request $request, int $bookingId): JsonResponse
    {
        $input = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $booking = $this->ownBooking($request, $bookingId);
        $changed = $this->actions->cancel($booking, $input['reason'] ?? null);

        return $this->afterAction($booking, $changed);
    }

    public function complete(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->ownBooking($request, $bookingId);
        $changed = $this->actions->complete($booking);

        return $this->afterAction($booking, $changed);
    }

    protected function ownBooking(Request $request, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->whereKey($bookingId)
            ->where('specialist_id', $this->specialist($request)->id)
            ->with(['service', 'user'])
            ->first();

        if (! $booking) {
            throw new ApiException('not_found', 'نوبت پیدا نشد.', 404);
        }

        return $booking;
    }

    protected function afterAction(Booking $booking, bool $changed): JsonResponse
    {
        return ApiResponse::success(
            StaffBookingPresenter::present($booking->fresh(['service', 'user'])),
            ['changed' => $changed],
        );
    }
}
