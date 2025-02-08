<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use App\Notifications\BookingNotification;
use App\Notifications\CustomerBookingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function show(Booking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'این نوبت قبلاً پرداخت شده است.');
        }

        return view('payment.show', compact('booking'));
    }

    public function process(Booking $booking)
    {
        try {
            if ($booking->payment_status === 'paid') {
                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'booking' => $booking,
                        'error_message' => 'این نوبت قبلاً پرداخت شده است.'
                    ]);
            }

            $result = $this->paymentService->createPayment($booking);

            if (!$result['success']) {
                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'booking' => $booking,
                        'error_message' => $result['message']
                    ]);
            }

            $booking->update([
                'payment_reference' => $result['reference']
            ]);

            return redirect($result['payment_url']);

        } catch (\Exception $e) {
            Log::error('Payment process failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => 'خطا در اتصال به درگاه پرداخت'
                ]);
        }
    }

    public function callback(Request $request, Booking $booking)
    {
        Log::info('Payment callback received', [
            'booking_id' => $booking->id,
            'authority' => $request->Authority,
            'status' => $request->Status
        ]);

        if ($request->Status !== 'OK') {
            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => 'پرداخت توسط کاربر لغو شد.'
                ]);
        }

        try {
            $result = $this->paymentService->verifyPayment(
                $request->Authority,
                $booking->prepayment_amount
            );

            if ($result['success']) {
                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'payment_ref' => $result['transaction_id'],
                    'payment_details' => $result,
                    'paid_at' => now()
                ]);

                $booking->specialist->notify(new BookingNotification($booking));
                $booking->user->notify(new CustomerBookingNotification($booking));

                return redirect()->route('payment.result')
                    ->with([
                        'success' => true,
                        'booking' => $booking
                    ]);
            }

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => $result['message']
                ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => 'خطا در تایید پرداخت'
                ]);
        }
    }

    public function result()
    {
        $success = session('success', false);
        $booking = session('booking');
        $error_message = session('error_message');

        if (!$booking) {
            return redirect()->route('bookings.index')
                ->with('error', 'اطلاعات نوبت یافت نشد.');
        }

        return view('payment.result', compact('success', 'booking', 'error_message'));
    }
}
