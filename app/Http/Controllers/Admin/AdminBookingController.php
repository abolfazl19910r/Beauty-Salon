<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        if ($request->has('date')) {
            $date = $request->date;
        } else {
            $date = today();
        }

        $query->whereDate('booking_time', $date);

        $bookings = $query->paginate(15);

        $totalBookings = Booking::whereDate('booking_time', $date)->count();

        $confirmedBookings = Booking::whereDate('booking_time', $date)
            ->where('status', 'confirmed')->count();

        $cancelledBookings = Booking::whereDate('booking_time', $date)
            ->where('status', 'cancelled')->count();

        return view('admin.bookings.index', compact(
            'bookings',
            'totalBookings',
            'confirmedBookings',
            'cancelledBookings'
        ));
    }

    public function create()
    {
        $users = User::all();
        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('admin.bookings.create', compact('users', 'services', 'specialists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:beauty_services,id',
            'specialist_id' => 'required|exists:specialists,id',
            'booking_time' => 'required|date',
            'status' => 'required|in:pending,confirmed,cancelled',
            'payment_status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string'
        ]);

        $booking = Booking::create($validated);

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
        // Model قبلاً توسط Laravel load شده، فقط relation ها رو eager load میکنیم
        $booking->load(['service', 'user', 'specialist']);
        return view('admin.bookings.show', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        if ($request->has('status') && !$request->has('user_id')) {
            $oldStatus = $booking->status;

            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,cancelled',
            ]);

            try {
                $booking->update(['status' => $validated['status']]);

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
        else {
            if ($request->has('booking_time')) {
                $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $request->merge([
                    'booking_time' => str_replace($persianDigits, $englishDigits, $request->booking_time)
                ]);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'service_id' => 'required|exists:beauty_services,id',
                'specialist_id' => 'required|exists:specialists,id',
                'booking_time' => 'required|date',
                'status' => 'required|in:pending,confirmed,cancelled',
                'payment_status' => 'required|in:paid,unpaid',
                'notes' => 'nullable|string'
            ]);

            $oldStatus = $booking->status;

            try {
                $booking->update($validated);

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
