<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function getData()
    {
        try {
            $stats = [
                'totalBookings' => Booking::count(),
                'todayBookings' => Booking::whereDate('created_at', today())->count(),
                'totalServices' => BeautyService::count(),
                'totalSpecialists' => Specialist::count(),
                'totalUsers' => User::count(),
                'totalRevenue' => Booking::where('payment_status', 'paid')->sum('prepayment_amount'),
            ];

            return response()->json([
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDailyRevenue(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->subDays(7));
            $endDate = $request->get('end_date', now());

            $dailyRevenue = Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(prepayment_amount) as total'),
                    DB::raw('COUNT(*) as bookings_count')
                )
                ->orderBy('date')
                ->get();

            $dailyRevenue = $dailyRevenue->map(function ($item) {
                $item->date = verta($item->date)->format('Y/m/d');
                return $item;
            });

            return response()->json(['dailyRevenue' => $dailyRevenue]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPopularServices()
    {
        try {
            $lastMonth = now()->subDays(30);
            $previousMonth = now()->subDays(60);

            $currentMonthServices = BeautyService::withCount(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }])
                ->withSum(['bookings' => function ($query) use ($lastMonth) {
                    $query->where('created_at', '>=', $lastMonth);
                }], 'prepayment_amount');

            $previousMonthServices = BeautyService::withCount(['bookings' => function ($query) use ($previousMonth, $lastMonth) {
                $query->whereBetween('created_at', [$previousMonth, $lastMonth]);
            }])->pluck('bookings_count', 'id');

            $popularServices = $currentMonthServices
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get(['id', 'name'])
                ->map(function($service) use ($previousMonthServices) {
                    $previousCount = $previousMonthServices[$service->id] ?? 0;
                    $currentCount = $service->bookings_count;

                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'bookings_count' => $currentCount,
                        'revenue' => $service->bookings_sum_prepayment_amount,
                        'trend' => $previousCount > 0
                            ? round((($currentCount - $previousCount) / $previousCount) * 100, 1)
                            : 100
                    ];
                });

            return response()->json(['popularServices' => $popularServices]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getActiveSpecialists()
    {
        try {
            $lastMonth = now()->subDays(30);

            $activeSpecialists = Specialist::withCount(['bookings' => function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            }])
                ->withSum(['bookings' => function ($query) use ($lastMonth) {
                    $query->where('created_at', '>=', $lastMonth);
                }], 'prepayment_amount')
                ->withAvg(['bookings' => function ($query) use ($lastMonth) {
                    $query->where('created_at', '>=', $lastMonth);
                }], 'rating')
                ->withCount(['bookings as successful_bookings' => function ($query) use ($lastMonth) {
                    $query->where('created_at', '>=', $lastMonth)
                        ->where('status', 'completed');
                }])
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get(['id', 'name'])
                ->map(function($specialist) {
                    $completion_rate = $specialist->bookings_count > 0
                        ? round(($specialist->successful_bookings / $specialist->bookings_count) * 100, 1)
                        : 0;

                    return [
                        'id' => $specialist->id,
                        'name' => $specialist->name,
                        'bookings_count' => $specialist->bookings_count,
                        'revenue' => $specialist->bookings_sum_prepayment_amount,
                        'rating' => round($specialist->bookings_avg_rating, 1),
                        'successful_bookings' => $specialist->successful_bookings,
                        'completion_rate' => $completion_rate,
                        'performance_score' => $this->calculatePerformanceScore($specialist),
                        'top_performer' => $completion_rate >= 90
                    ];
                });

            return response()->json(['activeSpecialists' => $activeSpecialists]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculatePerformanceScore($specialist): float
    {
        $scoreFactors = [
            'bookings' => $specialist->bookings_count * 10,
            'completion' => $specialist->completion_rate,
            'rating' => ($specialist->bookings_avg_rating ?? 0) * 20,
            'revenue' => min(($specialist->bookings_sum_prepayment_amount / 1000000), 100)
        ];

        return round(array_sum($scoreFactors) / 4, 1);
    }

    public function getDashboardByPeriod(string $period)
    {
        try {
            switch ($period) {
                case 'today':
                    $start = Carbon::today();
                    $end   = Carbon::now();
                    break;
                case 'week':
                    $start = Carbon::now()->subDays(6)->startOfDay();
                    $end   = Carbon::now();
                    break;
                case 'month':
                    $start = Carbon::now()->subDays(29)->startOfDay();
                    $end   = Carbon::now();
                    break;
                default:
                    return response()->json(['error' => 'Invalid period'], 400);
            }

            $stats = [
                'todayBookingsCount' => Booking::whereDate('booking_time', today())->count(),
                'totalRevenue'       => Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('prepayment_amount'),
                'usersCount'         => User::count(),
                'specialistsCount'   => Specialist::count(),
            ];

            if ($period === 'today') {
                $rows = Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy(DB::raw('HOUR(created_at)'))
                    ->select(
                        DB::raw('HOUR(created_at) as hour'),
                        DB::raw('SUM(prepayment_amount) as total'),
                        DB::raw('COUNT(*) as bookings_count')
                    )
                    ->orderBy('hour')
                    ->get();

                $dailyRevenue = $rows->map(function ($item) use ($start) {
                    $dt = $start->copy()->hour($item->hour);
                    return [
                        'date'           => verta($dt)->format('H:00'),
                        'total'          => (int) $item->total,
                        'bookings_count' => (int) $item->bookings_count,
                    ];
                })->values();
            } else {
                $rows = Booking::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(prepayment_amount) as total'),
                        DB::raw('COUNT(*) as bookings_count')
                    )
                    ->orderBy('date')
                    ->get();

                $dailyRevenue = $rows->map(function ($item) {
                    return [
                        'date'           => verta($item->date)->format('Y/m/d'),
                        'total'          => (int) $item->total,
                        'bookings_count' => (int) $item->bookings_count,
                    ];
                })->values();
            }

            $dateColumn = $period === 'today' ? 'booking_time' : 'created_at';

            $popularServices = BeautyService::withCount(['bookings' => function ($q) use ($start, $end, $dateColumn) {
                $q->whereBetween($dateColumn, [$start, $end]);
            }])
                ->withSum(['bookings' => function ($q) use ($start, $end, $dateColumn) {
                    $q->whereBetween($dateColumn, [$start, $end]);
                }], 'prepayment_amount')
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get(['id', 'name'])
                ->map(fn($s) => [
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'bookings_count' => $s->bookings_count,
                    'revenue'        => (int) $s->bookings_sum_prepayment_amount,
                ]);

            $recentBookings = Booking::with(['user', 'service'])
                ->whereBetween($dateColumn, [$start, $end])
                ->latest($dateColumn)
                ->take(5)
                ->get()
                ->map(fn($b) => [
                    'id'           => $b->id,
                    'user_name'    => optional($b->user)->name ?? '—',
                    'service_name' => optional($b->service)->name ?? '—',
                    'status'       => $b->status,
                    'booking_time' => verta($b->booking_time)->format('Y/m/d H:i'),
                ]);

            return response()->json([
                'stats'          => $stats,
                'dailyRevenue'   => $dailyRevenue,
                'popularServices'=> $popularServices,
                'recentBookings' => $recentBookings,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dashboard()
    {
        $todayBookingsCount = Booking::whereDate('booking_time', today())->count();
        $totalRevenue = Booking::where('payment_status', 'paid')->sum('prepayment_amount');
        $usersCount = User::count();
        $specialistsCount = Specialist::count();
        $rolesCount = Role::count();

        $roles = Role::withCount('users')->take(4)->get();

        $popularServices = BeautyService::withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->take(4)
            ->get();

        $topSpecialists = Specialist::withCount(['bookings' => function($query) {
            $query->whereDate('booking_time', today());
        }])
            ->withSum(['bookings' => function($query) {
                $query->where('payment_status', 'paid');
            }], 'prepayment_amount')
            ->orderBy('bookings_count', 'desc')
            ->take(3)
            ->get();

        $recentBookings = Booking::with(['user', 'service'])
            ->latest()
            ->take(4)
            ->get();

        $weeklyRevenue = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as total'),
                DB::raw('COUNT(*) as bookings_count')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->date = verta($item->date)->format('Y/m/d');
                return $item;
            });

        return view('admin.dashboard', compact(
            'todayBookingsCount',
            'totalRevenue',
            'usersCount',
            'specialistsCount',
            'rolesCount',
            'roles',
            'popularServices',
            'topSpecialists',
            'recentBookings',
            'weeklyRevenue'
        ));
    }
}
