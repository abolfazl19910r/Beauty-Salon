<?php

namespace App\Http\Controllers\Specialist\Report;

use App\Exports\SpecialistBookingsExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Specialist;
use App\Traits\HasJalaliDates;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SpecialistReportController extends Controller
{
    use HasJalaliDates;

    public function index(Request $request): View
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $specialistServices = $specialist->services;

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status', 'all');
        $serviceId = $request->input('service_id', 'all');

        $query = Booking::where('specialist_id', $specialist->id);

        if ($startDate && $endDate) {
            $startCarbon = $this->parseJalali($startDate)?->startOfDay() ?? now()->startOfDay();
            $endCarbon = $this->parseJalali($endDate)?->endOfDay() ?? now()->endOfDay();
            $query->whereBetween('booking_time', [$startCarbon, $endCarbon]);
        } else {
            $query->where('booking_time', '>=', now()->subDays(30));
        }

        if ($serviceId !== 'all') {
            $query->where('service_id', $serviceId);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $totalBookings = (clone $query)->count();
        $completedBookings = (clone $query)->where('status', 'completed')->count();
        $cancelledBookings = (clone $query)->where('status', 'cancelled')->count();

        $commissionRate = $specialist->getEffectiveCommissionRate();
        $totalRawRevenue = (clone $query)->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');
        $totalRevenue = $totalRawRevenue * (1 - $commissionRate / 100);

        if ($request->input('export') === 'excel') {
            $bookingsForExport = $query->with(['user', 'service'])->get();

            return Excel::download(new SpecialistBookingsExport($bookingsForExport, $commissionRate), 'Report.xlsx');
        }

        if ($request->input('export') === 'pdf') {
            $bookingsForPdf = $query->with(['user', 'service'])->get();

            $data = [
                'bookings' => $bookingsForPdf,
                'totalRevenue' => round($totalRevenue),
                'totalBookings' => $totalBookings,
                'completedBookings' => $completedBookings,
                'cancelledBookings' => $cancelledBookings,
                'specialist' => $specialist,
                'startDate' => $startDate ?? '30 روز اخیر',
                'endDate' => $endDate ?? 'امروز',
                'commissionRate' => $commissionRate,
            ];

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'vazir',
            ]);

            $html = view('specialist.reports.pdf', $data)->render();
            $mpdf->WriteHTML($html);

            return $mpdf->Output('Specialist-Report.pdf', 'D');
        }

        $bookings = $query->with(['user', 'service'])
            ->latest('booking_time')
            ->paginate(20)
            ->withQueryString();

        return view('specialist.reports.index', compact(
            'specialist', 'bookings', 'totalRevenue', 'totalBookings',
            'completedBookings', 'cancelledBookings', 'startDate', 'endDate',
            'status', 'specialistServices', 'serviceId', 'commissionRate'
        ));
    }
}
