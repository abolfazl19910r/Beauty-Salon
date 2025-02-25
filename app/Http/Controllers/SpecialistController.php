<?php

namespace App\Http\Controllers;

use App\Models\Specialist;
use App\Models\BeautyService;
use App\Models\SpecialistLeave;
use App\Models\SpecialistSchedule;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SpecialistController extends Controller
{

    public function search(Request $request)
    {
        $query = Specialist::whereNull('deleted_at');

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('service_id')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('beauty_services.id', $request->service_id);
            });
        }

        if ($request->has('sort')) {
            if ($request->sort == 'rating') {
                $query->withCount(['bookings as total_ratings' => function($q) {
                    $q->whereNotNull('rating');
                }])
                    ->withAvg('bookings', 'rating')
                    ->orderBy('bookings_avg_rating', $request->direction ?? 'desc');
            } else {
                $query->orderBy($request->sort, $request->direction ?? 'asc');
            }
        } else {
            $query->latest();
        }

        $specialists = $query->paginate($request->per_page ?? 10);

        if ($request->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.search', [
            'specialists' => $specialists,
            'search' => $request->name
        ]);
    }

    public function byService(BeautyService $service)
    {
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
            'service' => $service
        ]);
    }

    public function availability(Specialist $specialist, Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        $availability = [];
        $currentDate = $startDate->copy();

        $schedules = $specialist->schedules()
            ->where('is_active', true)
            ->get()
            ->keyBy('day_of_week');

        $leaves = $specialist->leaves()
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->get();

        $holidays = $specialist->holidays()
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        while ($currentDate <= $endDate) {
            $currentDateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;

            $isOnLeave = $leaves->contains(function($leave) use ($currentDate) {
                return Carbon::parse($leave->start_date)->startOfDay() <= $currentDate &&
                    Carbon::parse($leave->end_date)->endOfDay() >= $currentDate;
            });

            $isHoliday = in_array($currentDateString, $holidays);

            $hasSchedule = isset($schedules[$dayOfWeek]);

            $bookedSlots = $specialist->bookings()
                ->whereDate('booking_time', $currentDateString)
                ->whereNotIn('status', ['cancelled'])
                ->pluck('booking_time')
                ->map(function($time) {
                    return Carbon::parse($time)->format('H:i');
                })
                ->toArray();

            $availability[] = [
                'date' => $currentDateString,
                'day_of_week' => $dayOfWeek,
                'day_name' => $currentDate->locale('fa')->dayName,
                'is_available' => $hasSchedule && !$isOnLeave && !$isHoliday,
                'is_holiday' => $isHoliday,
                'is_on_leave' => $isOnLeave,
                'has_schedule' => $hasSchedule,
                'schedule' => $hasSchedule ? [
                    'start_time' => $schedules[$dayOfWeek]->start_time,
                    'end_time' => $schedules[$dayOfWeek]->end_time
                ] : null,
                'booked_slots_count' => count($bookedSlots)
            ];

            $currentDate->addDay();
        }

        if (request()->wantsJson()) {
            return response()->json([
                'specialist' => $specialist,
                'availability' => $availability,
                'year' => $year,
                'month' => $month
            ]);
        }

        return view('specialists.availability', [
            'specialist' => $specialist,
            'availability' => $availability,
            'year' => $year,
            'month' => $month
        ]);
    }

    public function availableSlots(Specialist $specialist, $date)
    {
        $date = Carbon::parse($date);
        $dateString = $date->format('Y-m-d');
        $dayOfWeek = $date->dayOfWeek;

        $isOnLeave = $specialist->leaves()
            ->where('status', 'approved')
            ->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString)
            ->exists();

        $isHoliday = $specialist->holidays()
            ->whereDate('date', $dateString)
            ->exists();

        if ($isOnLeave || $isHoliday) {
            return response()->json([
                'available_slots' => [],
                'message' => $isHoliday ? 'این روز تعطیل است' : 'متخصص در این روز مرخصی است'
            ]);
        }

        $schedule = $specialist->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json([
                'available_slots' => [],
                'message' => 'این روز جزو روزهای کاری متخصص نیست'
            ]);
        }

        $slots = [];
        $currentTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        $bookedSlots = $specialist->bookings()
            ->whereDate('booking_time', $dateString)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('booking_time')
            ->map(function($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        while ($currentTime < $endTime) {
            $timeSlot = $currentTime->format('H:i');

            if (!in_array($timeSlot, $bookedSlots)) {
                $slots[] = $timeSlot;
            }

            $currentTime->addMinutes(30);
        }

        return response()->json([
            'date' => $dateString,
            'available_slots' => $slots,
            'schedule' => [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time
            ]
        ]);
    }

    public function topRated()
    {
        $specialists = Specialist::whereNull('deleted_at')
            ->withCount(['bookings as completed_bookings' => function($query) {
                $query->where('status', 'completed');
            }])
            ->withCount(['bookings as rating_count' => function($query) {
                $query->whereNotNull('rating');
            }])
            ->withAvg('bookings', 'rating')
            ->having('bookings_avg_rating', '>=', 4)
            ->having('rating_count', '>=', 5)
            ->orderByDesc('bookings_avg_rating')
            ->orderByDesc('rating_count')
            ->take(10)
            ->get();

        if (request()->wantsJson()) {
            return response()->json($specialists);
        }

        return view('specialists.top-rated', [
            'specialists' => $specialists
        ]);
    }

    public function show(Specialist $specialist)
    {
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
            ->map(function($booking) {
                return [
                    'user_name' => $booking->user->name,
                    'rating' => $booking->rating,
                    'review' => $booking->review,
                    'date' => $booking->created_at->format('Y-m-d')
                ];
            });

        if (request()->wantsJson()) {
            return response()->json([
                'specialist' => $specialist,
                'reviews' => $reviews
            ]);
        }

        return view('specialists.show', [
            'specialist' => $specialist,
            'reviews' => $reviews
        ]);
    }
}
