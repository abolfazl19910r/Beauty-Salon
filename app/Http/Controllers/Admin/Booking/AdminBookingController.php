<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\StoreAdminBookingRequest;
use App\Http\Requests\Admin\Booking\UpdateAdminBookingRequest;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    public function getStats(Request $request)
    {
        $date = $request->date ? $request->date : today();

        $stats = [
            'total' => Booking::whereDate('booking_time', $date)->count(),
            'confirmed' => Booking::whereDate('booking_time', $date)
                ->where('status', 'confirmed')->count(),
            'cancelled' => Booking::whereDate('booking_time', $date)
                ->where('status', 'cancelled')->count(),
        ];

        return response()->json($stats);
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
        if ($request->isStatusOnly()) {
            $oldStatus = $booking->status;

            try {
                $booking->update(['status' => $request->validated()['status']]);

                if ($booking->status === 'cancelled' &&
                    $oldStatus !== 'cancelled' &&
                    $booking->payment_status === 'paid' &&
                    !$booking->refunded_at) {

                    $refundResult = $this->refundService->processRefund($booking);

                    if (!$refundResult) {
                        return redirect()->route('admin.bookings.index')
                            ->with('warning', 'نوبت لغو شد اما در برگشت وجه مشکلی پیش آمد. تیکت پشتیبانی ایجاد شد.');
                    }
                }

                $successMessage = match($booking->status) {
                    'confirmed' => 'نوبت با موفقیت تایید شد.',
                    'cancelled' => 'نوبت با موفقیت لغو شد.',
                    default => 'وضعیت نوبت با موفقیت بروزرسانی شد.'
                };

                return redirect()->route('admin.bookings.index')
                    ->with('success', $successMessage);

            } catch (\Exception $e) {
                return redirect()->route('admin.bookings.index')
                    ->with('error', 'خطایی در بروزرسانی وضعیت نوبت رخ داد. لطفا مجددا تلاش کنید.');
            }
        }

        $oldStatus = $booking->status;

        try {
            $booking->update($request->validated());

            if ($booking->status === 'cancelled' &&
                $oldStatus !== 'cancelled' &&
                $booking->payment_status === 'paid' &&
                !$booking->refunded_at) {

                $refundResult = $this->refundService->processRefund($booking);

                if (!$refundResult) {
                    return redirect()->route('admin.bookings.show', ['booking' => $booking->id])
                        ->with('warning', 'نوبت لغو شد اما در برگشت وجه مشکلی پیش آمد. تیکت پشتیبانی ایجاد شد.');
                }
            }

            $successMessage = match($booking->status) {
                'confirmed' => 'نوبت با موفقیت تایید شد.',
                'cancelled' => 'نوبت با موفقیت لغو شد.',
                default => 'وضعیت نوبت با موفقیت بروزرسانی شد.'
            };

            return redirect()->route('admin.bookings.show', ['booking' => $booking->id])
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            return redirect()->route('admin.bookings.show', ['booking' => $booking->id])
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
