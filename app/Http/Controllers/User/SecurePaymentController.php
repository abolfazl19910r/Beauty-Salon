<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\SecurePaymentService;
use App\Services\SecurityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurePaymentController extends Controller
{
    protected SecurePaymentService $paymentService;
    protected SecurityLogService $securityLogService;

    public function __construct(SecurePaymentService $paymentService, SecurityLogService $securityLogService)
    {
        $this->paymentService = $paymentService;
        $this->securityLogService = $securityLogService;
    }

    public function showCheckout(Booking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'این نوبت قبلاً پرداخت شده است.');
        }

        return view('payments.secure.checkout', compact('booking'));
    }

    public function initiate(Request $request, Booking $booking)
    {
        try {
            DB::beginTransaction();

            $payment = $this->paymentService->createPayment($booking);

            if (!$payment) {
                throw new \Exception('خطا در ایجاد تراکنش');
            }

            $this->securityLogService->logPaymentAttempt(
                $payment->id,
                $booking->prepayment_amount,
                true,
                ['initiation_type' => 'secure_checkout']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => route('payments.secure.verify', $payment->reference_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            $this->securityLogService->logPaymentAttempt(
                null,
                $booking->prepayment_amount,
                false,
                ['error' => $e->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد تراکنش. لطفا مجددا تلاش کنید.'
            ], 500);
        }
    }

    public function showVerification(string $reference)
    {
        $payment = Payment::where('reference_id', $reference)->firstOrFail();

        return view('payments.secure.verify', compact('payment'));
    }

    public function verify(Request $request, string $reference)
    {
        try {
            DB::beginTransaction();

            $result = $this->paymentService->verifyPayment($reference, $request->all());

            if ($result['success']) {
                $payment = Payment::where('reference_id', $reference)->firstOrFail();
                $booking = $payment->booking;

                /**
                 * R-Observers addendum: previously only the Payment model got gateway_reference —
                 * the Booking itself (payment_reference/payment_details) was left untouched, unlike
                 * every other payment path in the project. This meant bookings paid through this
                 * secure-checkout flow showed no payment reference on booking detail pages and were
                 * invisible to payment_details->method-based reports (paymentBreakdown() /
                 * getFinancialSummary()), silently excluded from all three method buckets.
                 */
                $booking->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $result['transaction_id'] ?? $reference,
                    'payment_details' => [
                        'method' => 'gateway',
                        'gateway_ref' => $result['transaction_id'] ?? $reference,
                    ],
                ]);

                $payment->update([
                    'status' => 'completed',
                    'gateway_response' => $result,
                    'gateway_reference' => $result['transaction_id']
                ]);

                $this->securityLogService->logPaymentAttempt(
                    $payment->id,
                    $payment->amount,
                    true,
                    $result
                );

                DB::commit();

                return redirect()->route('payments.secure.result', [
                    'reference' => $reference,
                    'status' => 'success'
                ]);
            }

            throw new \Exception($result['message'] ?? 'خطا در تایید پرداخت');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            $this->securityLogService->logPaymentAttempt(
                null,
                0,
                false,
                ['error' => $e->getMessage()]
            );

            return redirect()->route('payments.secure.result', [
                'reference' => $reference,
                'status' => 'failed',
                'message' => 'خطا در تایید پرداخت. لطفا با پشتیبانی تماس بگیرید.'
            ]);
        }
    }

    public function showResult(Request $request)
    {
        $success = $request->status === 'success';
        $reference = $request->reference;
        $message = $request->message;

        $payment = Payment::where('reference_id', $reference)->first();

        return view('payments.secure.result', compact('success', 'payment', 'message'));
    }

    public function checkStatus(string $reference)
    {
        $payment = Payment::where('reference_id', $reference)
            ->with('booking')
            ->firstOrFail();

        return response()->json([
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'gateway_reference' => $payment->gateway_reference,
            'booking_status' => $payment->booking->status
        ]);
    }

    protected function handlePaymentError(\Exception $e, ?Payment $payment = null)
    {
        Log::error('Payment processing error', [
            'payment_id' => $payment?->id,
            'error' => $e->getMessage()
        ]);

        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        }

        $this->securityLogService->logPaymentAttempt(
            $payment?->id,
            $payment?->amount ?? 0,
            false,
            ['error' => $e->getMessage()]
        );

        return response()->json([
            'success' => false,
            'message' => 'خطا در پردازش پرداخت. لطفا مجددا تلاش کنید.'
        ], 500);
    }
}
