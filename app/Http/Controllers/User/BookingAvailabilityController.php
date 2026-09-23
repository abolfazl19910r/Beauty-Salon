<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BookingAvailabilityController extends Controller
{
    public function __construct(
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected readonly HolidayRepositoryInterface $holidayRepository,
        protected readonly LeaveRepositoryInterface $leaveRepository,
        protected readonly SpecialistScheduleRepositoryInterface $specialistScheduleRepository,
    ) {}

    public function getAvailableTimeSlots(Request $request, $specialist, $date): JsonResponse
    {
        try {
            $specialistModel = $this->resolveSpecialist($specialist);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد در getAvailableTimeSlots', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['slots' => [], 'message' => 'متخصص مورد نظر یافت نشد'], 404);
        }

        $this->ensureSalonOwnership($specialistModel->salon_id);

        try {
            $carbonDate = Carbon::parse($date);
            $dayOfWeek = $carbonDate->dayOfWeek;
            $serviceDuration = $this->resolveServiceDuration($request->query('service_id'));

            if ($this->holidayRepository->existsOnDate($specialistModel->id, $date)) {
                return response()->json(['slots' => [], 'message' => 'این روز تعطیل است']);
            }

            if ($this->leaveRepository->hasApprovedLeaveOnDate($specialistModel->id, $date)) {
                return response()->json(['slots' => [], 'message' => 'متخصص در این روز مرخصی است']);
            }

            $schedule = $this->specialistScheduleRepository->findActiveForDay($specialistModel->id, $dayOfWeek);

            if (! $schedule) {
                return response()->json(['slots' => [], 'message' => 'این روز جزو روزهای کاری متخصص نیست']);
            }

            $availableSlots = $specialistModel->getAvailableSlots($date, $serviceDuration);

            if (empty($availableSlots)) {
                return response()->json(['slots' => [], 'message' => 'هیچ زمان خالی برای این تاریخ وجود ندارد']);
            }

            return response()->json([
                'slots' => $availableSlots,
                'service_duration' => $serviceDuration,
                'schedule' => [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start ?? null,
                    'break_end' => $schedule->break_end ?? null,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('خطا در دریافت اسلات‌های زمانی', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date' => $date,
                'service_id' => $request->query('service_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'خطا در دریافت ساعت‌های در دسترس'], 500);
        }
    }

    public function getAvailableDates($specialist): JsonResponse
    {
        try {
            $specialistModel = $this->resolveSpecialist($specialist);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد در getAvailableDates', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'متخصص مورد نظر یافت نشد', 'dates' => []], 404);
        }

        $this->ensureSalonOwnership($specialistModel->salon_id);

        try {
            $dates = [];
            $startDate = Carbon::today();

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                $schedule = $this->specialistScheduleRepository->findActiveForDay($specialistModel->id, $date->dayOfWeek);

                if (! $schedule) {
                    continue;
                }

                $hasLeave = $this->leaveRepository->hasApprovedLeaveOnDate($specialistModel->id, $date->format('Y-m-d'));

                $isHoliday = $this->holidayRepository->existsOnDate($specialistModel->id, $date->format('Y-m-d'));

                if (! $hasLeave && ! $isHoliday) {
                    $dates[] = $date->format('Y-m-d');
                }
            }

            return response()->json($dates);

        } catch (Exception $e) {
            Log::error('خطا در دریافت تاریخ‌ها', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'خطا در دریافت تاریخ‌ها', 'dates' => []], 500);
        }
    }

    public function getSpecialistsByService($serviceId): JsonResponse
    {
        try {
            $service = $this->resolveService($serviceId);
        } catch (Exception $e) {
            Log::error('خطا در دریافت متخصصین', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'خطا در دریافت متخصصین'], 500);
        }

        $this->ensureSalonOwnership($service->salon_id);

        try {
            // ⭐ ۲۰۲۶-۰۹-۲۴: photo_url برای نمایش عکس متخصص انتخاب‌شده در فرم رزرو.
            $specialists = $service->specialists()
                ->select('specialists.id', 'specialists.name', 'specialists.email', 'specialists.phone', 'specialists.photo_path')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'email' => $s->email,
                    'phone' => $s->phone,
                    'photo_url' => $s->photoUrl(),
                ]);

            return response()->json($specialists);

        } catch (Exception $e) {
            Log::error('خطا در دریافت متخصصین', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'خطا در دریافت متخصصین'], 500);
        }
    }

    private function resolveSpecialist($specialist): Specialist
    {
        if ($specialist instanceof Specialist) {
            return $specialist;
        }

        $specialistId = is_numeric($specialist) ? (int) $specialist : $specialist;

        return $this->specialistRepository->findOrFail($specialistId);
    }

    private function resolveService($service): BeautyService
    {
        if ($service instanceof BeautyService) {
            return $service;
        }

        return $this->beautyServiceRepository->findOrFail($service);
    }

    private function resolveServiceDuration(?string $serviceId): ?int
    {
        if (! $serviceId) {
            return null;
        }

        return $this->beautyServiceRepository->find($serviceId)?->duration;
    }
}
