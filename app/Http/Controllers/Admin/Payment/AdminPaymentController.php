<?php

namespace App\Http\Controllers\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function create(Request $request)
    {
        $booking = null;
        if ($request->has('booking_id')) {
            $booking = Booking::findOrFail($request->booking_id);
        }

        return view('admin.payments.create', compact('booking'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        $booking->update([
            'payment_status' => 'paid',
            'prepayment_amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->reference,
            'paid_at' => now(),
            'status' => 'confirmed'
        ]);

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'پرداخت با موفقیت ثبت شد.');
    }
}
