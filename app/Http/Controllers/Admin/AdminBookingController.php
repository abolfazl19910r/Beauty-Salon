<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBookingController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    public function index(Request $request)
    {
        $query = Booking::with(['user', 'service', 'specialist']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $date = $request->date;
            $query->whereDate('booking_time', $date);
        } else {
            $query->whereDate('booking_time', today());
        }

        $bookings = $query->orderBy('booking_time', 'desc')->paginate(10);

        return view('admin.bookings.index', compact('bookings'));
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

    public function show($id)
    {
        $booking = Booking::with(['service', 'user', 'specialist'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function update(Request $request, $booking_id)
    {
        $booking = Booking::findOrFail($booking_id);

        Log::info('درخواست آپدیت:', [
            'booking_id' => $booking->id,
            'current_status' => $booking->status,
            'requested_status' => $request->status
        ]);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $oldStatus = $booking->status;

        try {
            Log::info('قبل از آپدیت:', [
                'status' => $booking->status
            ]);

            $booking->update($validated);

            Log::info('بعد از آپدیت:', [
                'status' => $booking->fresh()->status
            ]);

            if ($booking->status === 'cancelled' &&
                $oldStatus !== 'cancelled' &&
                $booking->payment_status === 'paid' &&
                !$booking->refunded_at) {

                Log::info('شروع فرآیند استرداد وجه برای نوبت لغو شده', [
                    'booking_id' => $booking->id,
                    'amount' => $booking->prepayment_amount
                ]);

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
            Log::error('خطا در بروزرسانی وضعیت نوبت', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

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
