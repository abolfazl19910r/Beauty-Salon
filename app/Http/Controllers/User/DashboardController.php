<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\Announcement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $popularServices = BeautyService::withCount('bookings')
            ->orderByDesc('bookings_count')
            ->take(4)
            ->get();

        $userBookings = Booking::where('user_id', auth()->id())
            ->with(['service', 'specialist'])
            ->latest()
            ->take(5)
            ->get();

        $upcomingBookings = Booking::where('user_id', auth()->id())
            ->where('booking_time', '>', now())
            ->whereNotIn('status', ['cancelled'])
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->take(3)
            ->get();

        $announcements = Announcement::active()->byPriority()->take(3)->get();

        $topSpecialists = Specialist::withAvg('bookings', 'rating')
            ->orderByDesc('bookings_avg_rating')
            ->whereHas('bookings', function($query) {
                $query->whereNotNull('rating');
            })
            ->take(3)
            ->get();

        $recommendations = $this->getRecommendedServices();

        return view('dashboard', compact(
            'popularServices',
            'userBookings',
            'upcomingBookings',
            'announcements',
            'topSpecialists',
            'recommendations'
        ));
    }

    protected function getRecommendedServices()
    {
        $userServiceIds = Booking::where('user_id', auth()->id())
            ->pluck('service_id')
            ->unique()
            ->toArray();

        if (empty($userServiceIds)) {
            return BeautyService::latest()->take(3)->get();
        }

        return BeautyService::whereIn('category_id', function($query) use ($userServiceIds) {
            $query->select('category_id')
                ->from('beauty_services')
                ->whereIn('id', $userServiceIds);
        })
            ->whereNotIn('id', $userServiceIds)
            ->inRandomOrder()
            ->take(3)
            ->get();
    }
}
