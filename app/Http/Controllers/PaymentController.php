<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use App\Notifications\BookingNotification;
use App\Notifications\CustomerBookingNotification;
use App\Events\BookingCreated;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected SMSService $smsService;

    public function __construct(PaymentService $paymentService, SMSService $smsService)
    {
        $this->paymentService = $paymentService;
        $this->smsService = $smsService;
    }

    public function show(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403, 'دسترسی غیرمجاز');
        }

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', ['booking' => $booking])
                ->with('info', 'این نوبت قبلاً پرداخت شده است.');
        }

        return view('payment.show', ['booking' => $booking]);
    }

    public function process(Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'دسترسی غیرمجاز');
            }

            if ($booking->payment_status === 'paid') {
                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'booking' => $booking,
                        'error_message' => 'این نوبت قبلاً پرداخت شده است.'
                    ]);
            }
            $result = $this->paymentService->createPayment($booking);

            if (!isset($result['success']) || !$result['success']) {
                Log::warning('Payment creation failed', [
                    'booking_id' => $booking->id,
                    'result' => $result
                ]);

                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'booking' => $booking,
                        'error_message' => $result['message'] ?? 'خطا در اتصال به درگاه پرداخت'
                    ]);
            }

            $booking->update([
                'payment_reference' => $result['reference']
            ]);
            return redirect($result['payment_url']);

        } catch (\Exception $e) {
            Log::error('Exception in payment process', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => 'خطا در اتصال به درگاه پرداخت'
                ]);
        }
    }

    public function callback(Request $request)
    {
        try {
            $authority = $request->input('Authority');
            $status = $request->input('Status');

            if (!$authority) {
                Log::error('Authority not found in callback');
                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'error_message' => 'اطلاعات پرداخت یافت نشد.'
                    ]);
            }

            $booking = Booking::where('payment_reference', $authority)->first();

            if (!$booking) {
                Log::error('Booking not found', ['authority' => $authority]);
                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'error_message' => 'اطلاعات نوبت یافت نشد.'
                    ]);
            }

            if ($booking->user_id !== auth()->id()) {
                abort(403, 'دسترسی غیرمجاز');
            }

            if ($status !== 'OK') {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => 'customer',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'لغو پرداخت توسط کاربر'
                ]);

                return redirect()->route('payment.result')
                    ->with([
                        'success' => false,
                        'booking' => $booking,
                        'error_message' => 'پرداخت توسط کاربر لغو شد.'
                    ]);
            }

            $result = $this->paymentService->verifyPayment(
                $authority,
                (int) $booking->prepayment_amount
            );

            if ($result['success']) {
                DB::transaction(function() use ($booking, $result) {
                    $specialist = $booking->specialist;
                    $finalStatus = $specialist && $specialist->hasAutoConfirm()
                        ? 'confirmed'
                        : 'pending';

                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'payment_ref' => $result['transaction_id'],
                        'payment_details' => $result,
                        'paid_at' => now()
                    ]);

                    BookingCreated::dispatch($booking);

                    $booking->user->notify(new CustomerBookingNotification($booking));

                    if ($specialist) {
                        $specialist->notify(new BookingNotification($booking));
                    }

                    $message = sprintf(
                        "رزرو شما با موفقیت ثبت شد\n" .
                        "خدمت: %s\n" .
                        "تاریخ: %s ساعت %s\n" .
                        "کد پیگیری: #%s",
                        $booking->service->name,
                        verta($booking->booking_time)->format('Y/m/d'),
                        verta($booking->booking_time)->format('H:i'),
                        $booking->id
                    );
                    $this->smsService->send($booking->user->phone, $message);
                });

                session(['booking_id' => $booking->id]);

                return redirect()->route('payment.result')
                    ->with([
                        'success' => true,
                        'booking' => $booking
                    ]);
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_by' => 'system',
                'cancelled_at' => now(),
                'cancellation_reason' => 'پرداخت ناموفق'
            ]);

            Log::warning('Payment verification failed', [
                'booking_id' => $booking->id,
                'result' => $result
            ]);

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'booking' => $booking,
                    'error_message' => $result['message'] ?? 'پرداخت تایید نشد.'
                ]);

        } catch (\Exception $e) {
            Log::error('Exception in payment callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.result')
                ->with([
                    'success' => false,
                    'error_message' => 'خطا در پردازش پرداخت'
                ]);
        }
    }

    public function result()
    {
        $success = session('success', false);
        $booking = session('booking');
        $error_message = session('error_message');

        if (!$booking && session('booking_id')) {
            $booking = Booking::find(session('booking_id'));
        }

        if (!$booking) {
            return redirect()->route('bookings.index')
                ->with('error', 'اطلاعات نوبت یافت نشد.');
        }

        return view('payment.result', compact('success', 'booking', 'error_message'));
    }
}
