<?php

namespace App\Http\Controllers\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function __construct(protected readonly BookingRepositoryInterface $bookingRepository) {}

    public function create(Request $request): View
    {
        $booking = null;
        if ($request->has('booking_id')) {
            $booking = $this->bookingRepository->findOrFail($request->booking_id);
        }

        return view('admin.payments.create', compact('booking'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $booking = $this->bookingRepository->findOrFail($request->booking_id);

        $this->bookingRepository->update($booking, [
            'payment_status' => 'paid',
            'prepayment_amount' => $request->amount,
            'payment_details' => [
                'method' => $request->payment_method,
                'admin_recorded' => true,
                'notes' => $request->notes,
            ],
            'payment_reference' => $request->reference,
            'paid_at' => now(),
            'status' => 'confirmed',
        ]);

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'پرداخت با موفقیت ثبت شد.');
    }
}
