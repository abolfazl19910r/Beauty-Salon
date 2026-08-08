<?php

namespace App\Services\Admin\Report;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Traits\HasJalaliDates;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    use HasJalaliDates;

    /**
     * Convert start_date/end_date from input to Carbon and string.
     *
     * @param  array  $input  ['start_date' => ..., 'end_date' => ...]
     * @param  int  $defaultSubDays  Default number of days if not a date
     * @return array{start: Carbon, end: Carbon, startDate: string, endDate: string}
     */
    public function parseDateRange(array $input, int $defaultSubDays = 30): array
    {
        $startDate = $input['start_date'] ?? now()->subDays($defaultSubDays)->format('Y-m-d');
        $endDate = $input['end_date'] ?? now()->format('Y-m-d');

        if (! $this->validateDates($startDate, $endDate)) {
            $startDate = now()->subDays($defaultSubDays)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        return [
            'start' => Carbon::parse($startDate)->startOfDay(),
            'end' => Carbon::parse($endDate)->endOfDay(),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    public function validateDates(string $startDate, string $endDate): bool
    {
        try {
            return Carbon::parse($startDate)->lte(Carbon::parse($endDate));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSummary(Carbon $start, Carbon $end): array
    {
        return [
            'total_revenue' => Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),

            'total_bookings' => Booking::whereBetween('created_at', [$start, $end])->count(),

            'completed_bookings' => Booking::where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'cancelled_bookings' => Booking::where('status', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'pending_payments' => Booking::where('payment_status', 'unpaid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('prepayment_amount'),

            'average_booking_value' => (float) (Booking::where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->avg('prepayment_amount') ?? 0),
        ];
    }

    public function getFinancialSummary(Carbon $start, Carbon $end): array
    {
        $base = Booking::whereBetween('created_at', [$start, $end]);

        return [
            'total_revenue' => (clone $base)->where('payment_status', 'paid')->sum('prepayment_amount'),
            'total_bookings' => (clone $base)->count(),
            'completed_bookings' => (clone $base)->where('status', 'completed')->count(),
            'cancelled_bookings' => (clone $base)->where('status', 'cancelled')->count(),
            'pending_payments' => (clone $base)->where('payment_status', 'unpaid')->sum('prepayment_amount'),
            'average_booking_value' => (float) ((clone $base)->where('payment_status', 'paid')->avg('prepayment_amount') ?? 0),
            'wallet_payments' => (clone $base)->where('payment_details->method', 'wallet')->where('payment_status', 'paid')->sum('prepayment_amount'),
            /**
             * R-Observers: previously this summed everything with method != 'wallet' as "gateway
             * revenue" — including 'full_discount' (zero real money moved) and, after the
             * AdminPaymentController fix in this same phase, admin-manually-recorded cash/card/
             * transfer payments too. None of those went through the actual bank gateway. Since this
             * finding was discovered while payment_details->method was already being populated
             * correctly elsewhere, gateway_payments now only counts the real online-gateway method,
             * and admin-manual payments get their own bucket so reports don't misrepresent them as
             * gateway revenue (this endpoint isn't currently wired into any Blade/JS consumer, but
             * the field should be correct regardless of whether it's rendered yet).
             */
            'gateway_payments' => (clone $base)->whereIn('payment_details->method', ['gateway', 'wallet_gateway'])
                ->where('payment_status', 'paid')->sum('prepayment_amount'),
            'admin_manual_payments' => (clone $base)->where('payment_details->admin_recorded', true)
                ->where('payment_status', 'paid')->sum('prepayment_amount'),
            'total_discounts' => (clone $base)->sum('discount_amount'),
        ];
    }

    public function dailyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('date')
            ->get()
            ->map(function ($r) {
                $d = Carbon::parse($r->date);
                [$jy, $jm, $jd] = $this->toJalaliParts($d);

                return [
                    'label' => $jd.' '.$this->jalaliMonthName($jm),
                    'date' => $r->date,
                    'revenue' => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function weeklyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEARWEEK(created_at)'))
            ->select(
                DB::raw('DATE(MIN(created_at)) as week_start'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('week_start')
            ->get()
            ->map(function ($r) {
                $d = Carbon::parse($r->week_start);
                [$jy, $jm, $jd] = $this->toJalaliParts($d);

                return [
                    'label' => $jd.' '.$this->jalaliMonthName($jm),
                    'revenue' => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function monthlyRevenue(Carbon $start, Carbon $end): Collection
    {
        return Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(prepayment_amount) as revenue'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->orderBy('year')->orderBy('month')
            ->get()
            ->map(function ($r) {
                [$jy, $jm] = $this->toJalaliParts(Carbon::create($r->year, $r->month, 1));

                return [
                    'label' => $this->jalaliMonthName($jm).' '.$jy,
                    'revenue' => (int) $r->revenue,
                    'bookings' => (int) $r->total_bookings,
                ];
            });
    }

    public function getRevenueChartData(Carbon $start, Carbon $end, string $type): array
    {
        return match ($type) {
            'monthly' => $this->monthlyRevenue($start, $end)->toArray(),
            'weekly' => $this->weeklyRevenue($start, $end)->toArray(),
            default => $this->dailyRevenue($start, $end)->toArray(),
        };
    }

    public function monthlyBreakdown(Carbon $start, Carbon $end): Collection
    {
        return Booking::whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(CASE WHEN payment_status="paid" THEN prepayment_amount ELSE 0 END) as revenue'),
                DB::raw('COUNT(CASE WHEN status="cancelled" THEN 1 END) as cancelled_count'),
                DB::raw('COUNT(CASE WHEN status="completed" THEN 1 END) as completed_count')
            )
            ->orderBy('year')->orderBy('month')
            ->get()
            ->map(function ($r) {
                [$jy, $jm] = $this->toJalaliParts(Carbon::create($r->year, $r->month, 1));

                return [
                    'label' => $this->jalaliMonthName($jm).' '.$jy,
                    'total_bookings' => (int) $r->total_bookings,
                    'revenue' => (int) $r->revenue,
                    'cancelled_count' => (int) $r->cancelled_count,
                    'completed_count' => (int) $r->completed_count,
                ];
            });
    }

    public function paymentBreakdown(Carbon $start, Carbon $end): array
    {
        $base = Booking::whereBetween('created_at', [$start, $end])->where('payment_status', 'paid');

        $total = (clone $base)->count();

        if ($total === 0) {
            return ['gateway' => 0, 'wallet' => 0, 'admin_manual' => 0, 'mixed' => 0, 'total' => 0];
        }

        $gateway = (clone $base)->whereIn('payment_details->method', ['gateway', 'wallet_gateway'])->count();
        $wallet = (clone $base)->where('payment_details->method', 'wallet')->count();
        $adminManual = (clone $base)->where('payment_details->admin_recorded', true)->count();

        return [
            'gateway' => $gateway,
            'gateway_percent' => round($gateway / $total * 100, 1),
            'wallet' => $wallet,
            'wallet_percent' => round($wallet / $total * 100, 1),
            'admin_manual' => $adminManual,
            'admin_manual_percent' => round($adminManual / $total * 100, 1),
            'total' => $total,
        ];
    }

    public function serviceRevenue(Carbon $start, Carbon $end, int $limit = 8): Collection
    {
        return BeautyService::withCount(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status', '!=', 'cancelled'),
        ])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end]),
            ], 'prepayment_amount')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name,
                'bookings' => $s->bookings_count ?? 0,
                'revenue' => (int) ($s->revenue ?? 0),
            ]);
    }

    public function getPopularServicesForIndex(Carbon $start, Carbon $end): Collection
    {
        return $this->popularServices($start, $end, limit: 5);
    }

    public function getSpecialistPerformanceForIndex(Carbon $start, Carbon $end): Collection
    {
        return $this->specialistPerformance($start, $end)->take(10);
    }

    public function popularServices(Carbon $start, Carbon $end, int $limit = 5): Collection
    {
        return BeautyService::withCount(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status', '!=', 'cancelled'),
        ])
            ->withSum(['bookings as revenue' => fn ($q) => $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end]),
            ], 'prepayment_amount')
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();
    }

    public function specialistPerformance(Carbon $start, Carbon $end): Collection
    {
        return Specialist::with(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withCount(['bookings as total_bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['bookings as total_revenue' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid'),
            ], 'prepayment_amount')
            ->orderByDesc('total_bookings')
            ->get()
            ->map(function ($s) {
                $commissionRate = $s->getEffectiveCommissionRate();
                $totalRevenue = (float) ($s->total_revenue ?? 0);
                $specialistShare = $totalRevenue * (1 - $commissionRate / 100);

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'total_bookings' => $s->total_bookings ?? 0,
                    'total_revenue' => $totalRevenue,
                    'commission_rate' => $commissionRate,
                    'specialist_share' => round($specialistShare),
                    'booking_completion_rate' => $this->calcCompletionRate($s->bookings),
                    'customer_return_rate' => $this->calcReturnRate($s->bookings),
                    'performance_score' => $this->calcSpecialistScore($s),
                ];
            });
    }

    public function customerSatisfaction(Carbon $start, Carbon $end): Collection
    {
        return Booking::whereBetween('created_at', [$start, $end])
            ->whereNotNull('rating')
            ->select(
                'specialist_id',
                DB::raw('ROUND(AVG(rating), 2) as average_rating'),
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings')
            )
            ->groupBy('specialist_id')
            ->with('specialist:id,name')
            ->get()
            ->map(fn ($item) => [
                'specialist_name' => $item->specialist->name ?? '—',
                'average_rating' => $item->average_rating,
                'total_ratings' => $item->total_ratings,
                'satisfaction_rate' => $item->total_ratings > 0
                    ? round(($item->positive_ratings / $item->total_ratings) * 100, 1)
                    : 0,
            ]);
    }

    public function buildExportData(Carbon $start, Carbon $end, string $type): array
    {
        return [
            'summary' => $this->getFinancialSummary($start, $end),
            'paymentBreakdown' => $this->paymentBreakdown($start, $end),
            'rawBookings' => $this->getRawBookingsForExport($start, $end),
            'specialists' => Specialist::withCount(['bookings as total_bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
                ->withSum(['bookings as total_revenue' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid'),
                ], 'prepayment_amount')
                ->orderByDesc('total_bookings')
                ->get(),
            'services' => BeautyService::withCount(['bookings' => fn ($q) => $q->whereBetween('created_at', [$start, $end])])
                ->withSum(['bookings as revenue' => fn ($q) => $q->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end]),
                ], 'prepayment_amount')
                ->orderByDesc('bookings_count')
                ->limit(10)
                ->get(),
            'rows' => $this->getRowsForType($start, $end, $type),
        ];
    }

    /**
     * R-Observers (addendum): the aggregated sheets/PDF blocks (paid-only daily revenue, payment
     * breakdown) never reconcile against "total_bookings" (which counts every booking regardless of
     * status) without a raw listing — a user comparing "کل نوبت‌ها: 8" against the 7 rows shown in
     * "جزئیات درآمد" had no way to see which booking(s) accounted for the difference (unpaid/pending
     * bookings are counted in total_bookings but excluded from every paid-only breakdown). This
     * returns one row per real booking in the range, any status, so every aggregated number above it
     * can be manually cross-checked instead of trusted blindly.
     */
    public function getRawBookingsForExport(Carbon $start, Carbon $end): Collection
    {
        $statusLabels = [
            'pending' => 'در انتظار',
            'pending_payment' => 'در انتظار پرداخت',
            'confirmed' => 'تایید شده',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
        ];

        $paymentStatusLabels = [
            'paid' => 'پرداخت‌شده',
            'unpaid' => 'پرداخت‌نشده',
        ];

        $paymentMethodLabels = [
            'wallet' => 'کیف پول',
            'gateway' => 'درگاه بانکی',
            'wallet_gateway' => 'ترکیبی',
            'full_discount' => 'تخفیف کامل (بدون پرداخت)',
        ];

        $discountTypeLabels = [
            'percentage' => 'درصدی',
            'fixed' => 'مبلغ ثابت',
        ];

        $bookings = Booking::with(['user:id,name,phone', 'specialist:id,name,phone,commission_rate', 'service:id,name'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        // Batch-fetch discount code types (avoids one query per booking for the discount type column)
        $discountCodeTypes = \App\Models\DiscountCode::whereIn('code', $bookings->pluck('discount_code')->filter()->unique())
            ->pluck('type', 'code');

        return $bookings->map(function (Booking $booking) use ($statusLabels, $paymentStatusLabels, $paymentMethodLabels, $discountTypeLabels, $discountCodeTypes) {
            $method = $booking->payment_details['method'] ?? null;
            $isAdminManual = (bool) ($booking->payment_details['admin_recorded'] ?? false);

            $specialistShare = null;
            if ($booking->payment_status === 'paid' && $booking->specialist) {
                $rate = $booking->specialist->getEffectiveCommissionRate();
                $specialistShare = (int) round($booking->prepayment_amount * (1 - $rate / 100));
            }

            $discountType = $booking->discount_code
                ? ($discountTypeLabels[$discountCodeTypes[$booking->discount_code] ?? ''] ?? '—')
                : '—';

            return [
                'created_date' => $this->toJalaliDateString($booking->created_at),
                'created_time' => $booking->created_at->format('H:i'),
                'booking_date' => $booking->booking_time ? $this->toJalaliDateString($booking->booking_time) : '—',
                'booking_time' => $booking->booking_time ? $booking->booking_time->format('H:i') : '—',
                'customer' => $booking->user->name ?? '—',
                'customer_phone' => $booking->user->phone ?? '—',
                'service' => $booking->service->name ?? '—',
                'specialist' => $booking->specialist->name ?? '—',
                'specialist_phone' => $booking->specialist->phone ?? '—',
                'status' => $statusLabels[$booking->status] ?? $booking->status,
                'payment_status' => $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status,
                'payment_method' => $isAdminManual
                    ? 'ثبت دستی ادمین'
                    : ($paymentMethodLabels[$method] ?? '—'),
                'amount' => (int) $booking->prepayment_amount,
                'specialist_share' => $specialistShare,
                'discount_code' => $booking->discount_code ?? '—',
                'discount_type' => $discountType,
                'discount_amount' => (int) $booking->discount_amount,
                'payment_reference' => $booking->payment_reference ?? '—',
                'rating' => $booking->rating ?? '—',
                'review' => $booking->review ?? '—',
                'cancellation_reason' => $booking->status === 'cancelled' ? ($booking->cancellation_reason ?? '—') : '—',
                'refund_status' => $booking->refund_status ?? '—',
                'refunded_amount' => $booking->refunded_amount !== null ? (int) $booking->refunded_amount : null,
            ];
        });
    }

    private function getRowsForType(Carbon $start, Carbon $end, string $type): Collection
    {
        return match ($type) {
            'monthly' => $this->monthlyRevenue($start, $end),
            'weekly' => $this->weeklyRevenue($start, $end),
            default => $this->dailyRevenue($start, $end),
        };
    }

    public function calcFinancialTrends(string $startDate, string $endDate): array
    {
        $s = Carbon::parse($startDate);
        $e = Carbon::parse($endDate);
        $days = $s->diffInDays($e);
        $prevEnd = $s->copy()->subDay();
        $prevSt = $prevEnd->copy()->subDays($days);

        $curRev = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$s, $e])->sum('prepayment_amount');
        $prevRev = Booking::where('payment_status', 'paid')->whereBetween('created_at', [$prevSt, $prevEnd])->sum('prepayment_amount');

        return [
            'revenue_change' => $prevRev > 0
                ? round(($curRev - $prevRev) / $prevRev * 100, 2)
                : ($curRev > 0 ? 100 : 0),
            'previous_period' => ['start' => $prevSt->toDateString(), 'end' => $prevEnd->toDateString(), 'revenue' => $prevRev],
            'current_period' => ['start' => $startDate, 'end' => $endDate, 'revenue' => $curRev],
        ];
    }

    public function calcCompletionRate($bookings): float
    {
        $total = $bookings->count();

        return $total > 0
            ? round($bookings->where('status', 'completed')->count() / $total * 100, 1)
            : 0;
    }

    public function calcReturnRate($bookings): float
    {
        $customers = $bookings->pluck('user_id')->unique();
        if ($customers->count() === 0) {
            return 0;
        }

        $returnCount = $bookings->groupBy('user_id')->filter(fn ($b) => $b->count() > 1)->count();

        return round($returnCount / $customers->count() * 100, 1);
    }

    public function calcSpecialistScore(Specialist $specialist): float
    {
        $c = $this->calcCompletionRate($specialist->bookings);
        $r = $this->calcReturnRate($specialist->bookings);
        $b = min(($specialist->total_bookings ?? 0) / 100, 1) * 20;
        $v = min(($specialist->total_revenue ?? 0) / 1_000_000, 1) * 20;

        return round($c * 0.3 + $r * 0.3 + $b + $v, 2);
    }
}
