<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialistController extends Controller
{
    public function __construct(protected readonly SpecialistRepositoryInterface $specialistRepository) {}

    public function search(Request $request): View|JsonResponse
    {
        $filters = [];

        if ($request->has('name')) {
            $filters['name'] = $request->name;
        }

        if ($request->has('service_id')) {
            $filters['service_id'] = $request->service_id;
        }

        if ($request->has('sort')) {
            $filters['sort'] = $request->sort;
            $filters['direction'] = $request->direction;
        }

        $specialists = $this->specialistRepository->searchPaginated($filters, $request->per_page ?? 10);

        if ($request->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.search', [
            'specialists' => $specialists,
            'search' => $request->name,
        ]);
    }

    public function byService(BeautyService $service): View|JsonResponse
    {
        $this->ensureSalonOwnership($service->salon_id);

        $specialists = $service->specialists()
            ->whereNull('specialists.deleted_at')
            ->withCount(['bookings as completed_bookings' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withAvg('bookings', 'rating')
            ->paginate(15);

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

        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
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

        $specialist->rating_avg = $specialist->bookings()
            ->whereNotNull('rating')
            ->avg('rating');

        $specialist->rating_count = $specialist->bookings()
            ->whereNotNull('rating')
            ->count();

        $specialist->completed_bookings = $specialist->bookings()
            ->where('status', 'completed')
            ->count();

        $reviews = $specialist->bookings()
            ->with('user:id,name')
            ->whereNotNull('review')
            ->whereNotNull('rating')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
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
