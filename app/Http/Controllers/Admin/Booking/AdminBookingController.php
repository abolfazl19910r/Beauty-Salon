<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\StoreAdminBookingRequest;
use App\Http\Requests\Admin\Booking\UpdateAdminBookingRequest;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\Booking\AdminBookingService;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    protected AdminBookingService $bookingService;

    public function __construct(AdminBookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function getStats(Request $request)
    {
        return response()->json($this->bookingService->getStats($request->date));
    }

    public function index(Request $request)
    {
        $query = Booking::with(['user', 'specialist', 'service'])->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $hasDateFilter = $request->filled('date');

        if ($hasDateFilter) {
            $date = $request->date;
            $query->whereDate('booking_time', $date);
        }

        $bookings = $query->paginate(15)->withQueryString();

        $statsQuery = Booking::query();
        if ($hasDateFilter) {
            $statsQuery->whereDate('booking_time', $date);
        }

        $totalBookings = (clone $statsQuery)->count();
        $confirmedBookings = (clone $statsQuery)->where('status', 'confirmed')->count();
        $cancelledBookings = (clone $statsQuery)->where('status', 'cancelled')->count();

        return view('admin.bookings.index', compact(
            'bookings',
            'totalBookings',
            'confirmedBookings',
            'cancelledBookings',
            'hasDateFilter'
        ));
    }

    public function create()
    {
        $users = User::all();
        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('admin.bookings.create', compact('users', 'services', 'specialists'));
    }

    public function store(StoreAdminBookingRequest $request)
    {
        $booking = Booking::create($request->validated());

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    public function edit(Booking $booking)
    {
        $users = User::all();
        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('admin.bookings.edit', compact('booking', 'users', 'services', 'specialists'));
    }

    public function show(Booking $booking)
    {
        $booking->load(['service', 'user', 'specialist']);
        return view('admin.bookings.show', compact('booking'));
    }

    public function update(UpdateAdminBookingRequest $request, Booking $booking)
    {
        $redirectRoute  = $request->isStatusOnly() ? 'admin.bookings.index' : 'admin.bookings.show';
        $redirectParams = $request->isStatusOnly() ? [] : ['booking' => $booking->id];

        try {
            $result = $request->isStatusOnly()
                ? $this->bookingService->updateStatus($booking, $request->validated()['status'])
                : $this->bookingService->updateFull($booking, $request->validated());

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'خطایی در بروزرسانی وضعیت نوبت رخ داد. لطفا مجددا تلاش کنید.');
        }
    }

    public function destroy(Booking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('admin.bookings.index')
                ->with('error', 'نوبت‌های پرداخت شده را نمی‌توان حذف کرد. ابتدا آن را لغو کنید.');
        }

        $booking->delete();
        return redirect()->route('admin.bookings.index')
            ->with('success', 'نوبت با موفقیت حذف شد.');
    }
}
