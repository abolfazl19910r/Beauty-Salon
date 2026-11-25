<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Services\Booking\SalonFreeSlots;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ⭐ بسته‌ی ۵الف: روزها و ساعت‌های خالی فرم رزرو قالب «شاهکار» وقتی مشتری خدمت یا کارشناس را عوض می‌کند (نمایش اول سمت سرور
 * است). همان SalonFreeSlots؛ خدمت و کارشناس فقط از سالن جاری (scope ‏BelongsToSalon). رزرو قطعی همچنان با bookings.create.
 */
class BookingQuickSlotsController extends Controller
{
    public function __invoke(Request $request, CurrentSalon $currentSalon, SalonFreeSlots $freeSlots): JsonResponse
    {
        $data = $request->validate([
            'service' => ['required', 'integer'],
            'specialist' => ['nullable', 'integer'],
        ]);
        $salon = $currentSalon->get();
        $service = BeautyService::query()->find($data['service']);
        if ($salon === null || $service === null) {
            return response()->json(['days' => []], 404);
        }
        $specialistId = isset($data['specialist']) ? (int) $data['specialist'] : null;
        if ($specialistId !== null && ! $service->specialists()->where('specialists.id', $specialistId)->exists()) {
            return response()->json(['days' => []], 404);
        }

        return response()->json([
            'days' => $freeSlots->daysForService($salon, $service, $specialistId),
            // کارشناسان همین خدمت برای فهرست «کارشناس» فرم (مسیر bookings.service-specialists پشت ورود است)
            'specialists' => $service->specialists()->orderBy('specialists.id')->get(['specialists.id', 'specialists.name'])
                ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name])->values(),
        ]);
    }
}
