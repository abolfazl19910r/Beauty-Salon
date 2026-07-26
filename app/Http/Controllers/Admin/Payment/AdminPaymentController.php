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

        /**
         * R-Observers: payment_method is not a real column on `bookings` (see payment_details JSON
         * column instead — every other payment path in the project, e.g. PaymentController, stores
         * the method under payment_details->method). This was previously documented as fixed, but
         * the fix was never actually applied here: $booking->update(['payment_method' => ...]) was
         * silently dropped by mass-assignment because the key isn't in Booking::$fillable, so manual
         * admin-recorded payments never actually stored which method was used.
         */
        $booking->update([
            'payment_status' => 'paid',
            'prepayment_amount' => $request->amount,
            'payment_details' => [
                'method' => $request->payment_method,
                'admin_recorded' => true,
                'notes' => $request->notes,
            ],
            'payment_reference' => $request->reference,
            'paid_at' => now(),
            'status' => 'confirmed'
        ]);

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'پرداخت با موفقیت ثبت شد.');
    }
}
