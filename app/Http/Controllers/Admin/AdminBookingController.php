<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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

    public function index()
    {
        $bookings = Booking::with(['user', 'specialist', 'service'])
            ->latest()
            ->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        return view('admin.bookings.show', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $oldStatus = $booking->status;
        $booking->update($validated);

        if ($booking->status === 'cancelled' &&
            $oldStatus !== 'cancelled' &&
            $booking->payment_status === 'paid' &&
            !$booking->refunded_at) {

            Log::info('Processing refund for cancelled booking', [
                'booking_id' => $booking->id,
                'amount' => $booking->prepayment_amount
            ]);

            $refundResult = $this->refundService->processRefund($booking);

            if (!$refundResult) {
                return redirect()->route('admin.bookings.index')
                    ->with('warning', 'نوبت لغو شد اما در برگشت وجه مشکلی پیش آمد. تیکت پشتیبانی ایجاد شد.');
            }
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', 'وضعیت نوبت با موفقیت بروزرسانی شد.');
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
