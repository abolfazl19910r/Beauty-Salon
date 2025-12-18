<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function process(Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                abort(403);
            }

            if ($booking->payment_status === 'paid') {
                return redirect()->route('payment.result')->with(['success' => true, 'booking' => $booking]);
            }

            Log::info('🔵 Payment Process Started', [
                'booking_id' => $booking->id,
                'amount_toman' => $booking->prepayment_amount,
                'user_id' => auth()->id(),
                'user_phone' => auth()->user()->phone
            ]);

            $result = $this->paymentService->createPayment($booking);

            Log::info('🟡 Payment Gateway Response', [
                'booking_id' => $booking->id,
                'result' => $result
            ]);

            if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                Log::info('✅ Redirecting to payment gateway', [
                    'booking_id' => $booking->id,
                    'url' => $result['payment_url']
                ]);
                return redirect($result['payment_url']);
            }

            $errorMessage = $result['message'] ?? 'در حال حاضر امکان اتصال به درگاه بانکی وجود ندارد.';
            Log::error('❌ Payment Gateway Error', [
                'booking_id' => $booking->id,
                'error' => $errorMessage,
                'full_result' => $result
            ]);

            return back()->with('error', 'خطای بانک: ' . $errorMessage);

        } catch (\Exception $e) {
            Log::error('💥 Payment Process Exception', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'خطای سیستمی در فرآیند پرداخت: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            Log::info('🔵 Payment Callback Received', [
                'all_params' => $request->all(),
                'Authority' => $request->Authority,
                'Status' => $request->Status
            ]);

            $result = $this->paymentService->verifyPayment($request);

            Log::info('🟡 Payment Verification Result', [
                'result' => $result
            ]);

            $booking = Booking::findOrFail($result['booking_id']);

            if ($result['success']) {
                Log::info('✅ Payment Verified Successfully', [
                    'booking_id' => $booking->id,
                    'ref_id' => $result['ref_id'] ?? null
                ]);

                $booking->refresh();

                if ($booking->payment_status !== 'paid') {
                    Log::info('💳 Updating booking payment status', [
                        'booking_id' => $booking->id,
                        'current_payment_status' => $booking->payment_status
                    ]);

                    $booking->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'payment_ref' => $result['ref_id'] ?? $result['reference']
                    ]);

                    Log::info('📨 Booking Updated Successfully', [
                        'booking_id' => $booking->id,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status
                    ]);
                } else {
                    Log::warning('⚠️ Booking already paid, skipping update', [
                        'booking_id' => $booking->id,
                        'payment_status' => $booking->payment_status,
                        'paid_at' => $booking->paid_at
                    ]);
                }

                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.');
            }

            Log::warning('⚠️ Payment Failed', [
                'booking_id' => $booking->id,
                'message' => $result['message'] ?? 'Unknown'
            ]);

            $booking->update(['status' => 'cancelled', 'cancellation_reason' => 'پرداخت ناموفق']);

            return redirect()->route('bookings.failed')
                ->with('error', $result['message'] ?? 'پرداخت ناموفق بود');

        } catch (\Exception $e) {
            Log::error('💥 Payment Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('bookings.failed')
                ->with('error', 'خطا در تایید تراکنش');
        }
    }

    public function result()
    {
        $success = session('success', false);
        $booking = session('booking');
        $error_message = session('error_message');

        if (!$booking) return redirect()->route('home');

        return view('payment.result', compact('success', 'booking', 'error_message'));
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
}
