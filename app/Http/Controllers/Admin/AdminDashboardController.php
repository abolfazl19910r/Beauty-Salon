<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function getData()
    {
        $stats = [
            'totalBookings' => Booking::count(),
            'todayBookings' => Booking::whereDate('created_at', today())->count(),
            'totalServices' => BeautyService::count(),
            'totalSpecialists' => Specialist::count(),
            'totalUsers' => User::count(),
            'totalRevenue' => Booking::where('payment_status', 'paid')->sum('prepayment_amount'),
        ];

        $dailyRevenue = Booking::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as total')
            )
            ->orderBy('date')
            ->get();

        $popularServices = BeautyService::withCount(['bookings' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        }])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get(['id', 'name', 'bookings_count']);

        $activeSpecialists = Specialist::withCount(['bookings' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        }])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get(['id', 'name', 'bookings_count']);

        return response()->json([
            'stats' => $stats,
            'dailyRevenue' => $dailyRevenue,
            'popularServices' => $popularServices,
            'activeSpecialists' => $activeSpecialists,
        ]);
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }
}
