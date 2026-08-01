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
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SecurePaymentController extends Controller
{
    public function __construct(protected readonly SecurePaymentService $paymentService, protected readonly SecurityLogService $securityLogService)
    {
    }

    public function showOtp(): View|RedirectResponse
    {
        $user = auth()->user();

        if (session('2fa_verified')) {
            return redirect()->to(session('secure_payment_intended_url', route('bookings.index')));
        }

        if (!$user->two_factor_enabled) {
            return redirect()->route('security.2fa');
        }

        return view('payments.secure.otp');
    }

    public function showCheckout(Booking $booking): View|RedirectResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'این نوبت قبلاً پرداخت شده است.');
        }

        return view('payments.secure.checkout', compact('booking'));
    }

    public function initiate(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'این نوبت قبلاً پرداخت شده است.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment = $this->paymentService->createPayment($booking);

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
            Log::error('Secure payment initiation failed', [
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

    public function showVerification(string $reference): View|RedirectResponse
    {
        $payment = Payment::where('reference_id', $reference)->with('booking')->firstOrFail();

        $this->authorize('pay', $payment->booking);

        if ($payment->isCompleted()) {
            return redirect()->route('payments.secure.result', [
                'reference' => $reference,
                'status' => 'success',
            ]);
        }

        if ($payment->isFailed() || $payment->isExpired()) {
            return redirect()->route('payments.secure.result', [
                'reference' => $reference,
                'status' => 'failed',
                'message' => 'مهلت این تراکنش به پایان رسیده یا قبلاً ناموفق اعلام شده است.',
            ]);
        }

        return view('payments.secure.verify', [
            'payment' => $payment,
            'booking' => $payment->booking,
        ]);
    }

    public function verify(Request $request, string $reference): RedirectResponse
    {
        $payment = Payment::where('reference_id', $reference)->with('booking')->firstOrFail();

        $this->authorize('pay', $payment->booking);

        try {
            DB::beginTransaction();

            $result = $this->paymentService->verifyPayment($reference);

            if ($result['success']) {
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
                    'payment_details' => array_merge($booking->payment_details ?? [], [
                        'method' => 'gateway',
                        'gateway_ref' => $result['transaction_id'] ?? $reference,
                        'secure_payment' => true,
                    ]),
                ]);

                if (!$payment->isCompleted()) {
                    $payment->update([
                        'status' => 'completed',
                        'gateway_response' => $result,
                        'gateway_reference' => $result['transaction_id'] ?? $reference,
                        'paid_at' => now(),
                    ]);
                }

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

            DB::commit();

            $this->securityLogService->logPaymentAttempt(
                $payment->id,
                $payment->amount,
                false,
                $result
            );

            return redirect()->route('payments.secure.result', [
                'reference' => $reference,
                'status' => 'failed',
                'message' => $result['message'] ?? 'خطا در تایید پرداخت',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Secure payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            $this->securityLogService->logPaymentAttempt(
                $payment->id,
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

    public function showResult(Request $request): View
    {
        $success = $request->status === 'success';
        $reference = $request->reference;
        $message = $request->message;

        $payment = Payment::where('reference_id', $reference)->with('booking')->first();

        if ($payment) {
            $this->authorize('pay', $payment->booking);
        }

        $booking = $payment?->booking;

        return view('payments.secure.result', compact('success', 'payment', 'booking', 'message'));
    }

    public function checkStatus(string $reference): JsonResponse
    {
        $payment = Payment::where('reference_id', $reference)
            ->with('booking')
            ->firstOrFail();

        $this->authorize('pay', $payment->booking);

        return response()->json([
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'gateway_reference' => $payment->gateway_reference,
            'remaining_seconds' => $payment->getRemainingTime(),
            'booking_status' => $payment->booking->status
        ]);
    }
}
