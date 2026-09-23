<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialistController extends Controller
{
    public function __construct(
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function search(Request $request): View|JsonResponse
    {
        $filters = [];

        if (filled($request->name)) {
            $filters['name'] = (string) $request->name;
        }

        if (filled($request->service_id)) {
            $filters['service_id'] = (int) $request->service_id;
        }

        // ⭐ ۲۰۲۶-۰۹-۲۴: قبلاً sort/direction مستقیم به orderBy می‌رفت — ستون یا جهت نامعتبر (مثلاً از
        // URL دستکاری‌شده) خطای ۵۰۰ SQL/InvalidArgumentException می‌داد. حالا whitelist.
        if (in_array($request->sort, ['name', 'rating'], true)) {
            $filters['sort'] = $request->sort;
            $filters['direction'] = in_array($request->direction, ['asc', 'desc'], true)
                ? $request->direction
                : ($request->sort === 'rating' ? 'desc' : 'asc');
        }

        $perPage = min(max((int) ($request->per_page ?? 12), 1), 48);
        $specialists = $this->specialistRepository->searchPaginated($filters, $perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.search', [
            'specialists' => $specialists,
            'search' => $request->name,
            'serviceId' => $filters['service_id'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'services' => BeautyService::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function byService(BeautyService $service): View|JsonResponse
    {
        $this->ensureSalonOwnership($service->salon_id);

        $specialists = $this->specialistRepository->paginateByService($service->id, 15);

        if (request()->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.by-service', [
            'specialists' => $specialists,
            'service' => $service,
        ]);
    }

    public function availableSlots(Specialist $specialist, $date, Request $request): JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        $duration = $request->service_duration;
        $slots = $specialist->getAvailableSlots($date, $duration);

        if (empty($slots)) {
            return response()->json([
                'available_slots' => [],
                'message' => 'در این تاریخ زمانی برای رزرو یافت نشد.',
            ]);
        }

        return response()->json([
            'date' => $date,
            'available_slots' => $slots,
        ]);
    }

    public function availability(Specialist $specialist, Request $request): View|JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        // ⭐ ۲۰۲۶-۰۹-۲۴: ورودی نامعتبر (month=13، year=abc) قبلاً Carbon::createFromFormat رو می‌شکست.
        $month = str_pad((string) min(max((int) ($request->month ?? date('m')), 1), 12), 2, '0', STR_PAD_LEFT);
        $year = (string) min(max((int) ($request->year ?? date('Y')), 2000), 2100);
        $yearMonth = "{$year}-{$month}";

        $availabilityData = $specialist->getMonthAvailability($yearMonth);

        if (request()->wantsJson()) {
            return response()->json([
                'specialist' => $specialist,
                'availability' => $availabilityData,
                'year' => $year,
                'month' => $month,
            ]);
        }

        return view('specialists.availability', compact('specialist', 'availabilityData', 'year', 'month'));
    }

    public function getAvailableDates(Specialist $specialist): JsonResponse
    {
        try {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addDays(30);

            $availableDates = [];

            while ($startDate->lte($endDate)) {
                $dateString = $startDate->format('Y-m-d');

                $hasSchedule = $specialist->schedules()
                    ->where('day_of_week', $startDate->dayOfWeek)
                    ->where('is_active', true)
                    ->exists();

                if ($hasSchedule) {
                    $availableSlots = $specialist->getAvailableSlots($dateString);

                    if (! empty($availableSlots)) {
                        $availableDates[] = $dateString;
                    }
                }

                $startDate->addDay();
            }

            return response()->json($availableDates);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function topRated(): View|JsonResponse
    {
        $specialists = $this->specialistRepository->getTopRated(10);

        if (request()->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.top-rated', [
            'specialists' => $specialists,
        ]);
    }

    public function show(Specialist $specialist): View|JsonResponse
    {
        $this->ensureSalonOwnership($specialist->salon_id);

        if ($specialist->deleted_at) {
            abort(404);
        }

        $specialist->load(['services', 'schedules']);

        $ratingStats = $this->bookingRepository->getRatingStatsForSpecialist($specialist->id);
        $specialist->rating_avg = $ratingStats['avg'];
        $specialist->rating_count = $ratingStats['count'];
        $specialist->completed_bookings = $ratingStats['completed'];

        $reviews = $this->bookingRepository->getRecentReviewsForSpecialist($specialist->id, 5)
            ->map(function ($booking) {
                return [
                    'user_name' => $booking->user->name,
                    'rating' => $booking->rating,
                    'review' => $booking->review,
                    'date' => $booking->created_at->format('Y-m-d'),
                ];
            });

        if (request()->wantsJson()) {
            return response()->json([
                'specialist' => $specialist,
                'reviews' => $reviews,
            ]);
        }

        return view('specialists.show', [
            'specialist' => $specialist,
            'reviews' => $reviews,
        ]);
    }
}
