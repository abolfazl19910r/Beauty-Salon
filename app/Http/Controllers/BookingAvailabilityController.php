<?php

namespace App\Http\Controllers;

use App\Models\BeautyService;
use App\Models\Specialist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BookingAvailabilityController extends Controller
{
    public function getAvailableTimeSlots(Request $request, $specialist, $date): JsonResponse
    {
        try {
            $specialistModel = $this->resolveSpecialist($specialist);

            $carbonDate = Carbon::parse($date);
            $dayOfWeek = $carbonDate->dayOfWeek;
            $serviceDuration = $this->resolveServiceDuration($request->query('service_id'));

            if ($specialistModel->holidays()->whereDate('date', $date)->exists()) {
                return response()->json(['slots' => [], 'message' => 'این روز تعطیل است']);
            }

            if ($specialistModel->leaves()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists()) {
                return response()->json(['slots' => [], 'message' => 'متخصص در این روز مرخصی است']);
            }

            $schedule = $specialistModel->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (! $schedule) {
                return response()->json(['slots' => [], 'message' => 'این روز جزو روزهای کاری متخصص نیست']);
            }

            $availableSlots = $specialistModel->getAvailableSlots($date, $serviceDuration);

            if (empty($availableSlots)) {
                return response()->json(['slots' => [], 'message' => 'هیچ زمان خالی برای این تاریخ وجود ندارد']);
            }

            return response()->json([
                'slots'            => $availableSlots,
                'service_duration' => $serviceDuration,
                'schedule'         => [
                    'start_time'  => $schedule->start_time,
                    'end_time'    => $schedule->end_time,
                    'break_start' => $schedule->break_start ?? null,
                    'break_end'   => $schedule->break_end ?? null,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد در getAvailableTimeSlots', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date'  => $date,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['slots' => [], 'message' => 'متخصص مورد نظر یافت نشد'], 404);

        } catch (Exception $e) {
            Log::error('خطا در دریافت اسلات‌های زمانی', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date'       => $date,
                'service_id' => $request->query('service_id'),
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['error' => 'خطا در دریافت ساعت‌های در دسترس'], 500);
        }
    }

    public function getAvailableDates($specialist): JsonResponse
    {
        try {
            $specialistModel = $this->resolveSpecialist($specialist);

            $dates = [];
            $startDate = Carbon::today();

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                $schedule = $specialistModel->schedules()
                    ->where('day_of_week', $date->dayOfWeek)
                    ->where('is_active', true)
                    ->first();

                if (! $schedule) {
                    continue;
                }

                $hasLeave = $specialistModel->leaves()
                    ->where('start_date', '<=', $date->format('Y-m-d'))
                    ->where('end_date', '>=', $date->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->exists();

                $isHoliday = $specialistModel->holidays()
                    ->whereDate('date', $date)
                    ->exists();

                if (! $hasLeave && ! $isHoliday) {
                    $dates[] = $date->format('Y-m-d');
                }
            }

            return response()->json($dates);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد در getAvailableDates', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'متخصص مورد نظر یافت نشد', 'dates' => []], 404);

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
            $service = BeautyService::findOrFail($serviceId);

            $specialists = $service->specialists()
                ->select('specialists.id', 'specialists.name', 'specialists.email', 'specialists.phone')
                ->get();

            return response()->json($specialists);

        } catch (Exception $e) {
            Log::error('خطا در دریافت متخصصین', [
                'service_id' => $serviceId,
                'error'      => $e->getMessage(),
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

        return Specialist::findOrFail($specialistId);
    }

    private function resolveServiceDuration(?string $serviceId): ?int
    {
        if (! $serviceId) {
            return null;
        }

        return BeautyService::find($serviceId)?->duration;
    }
}
