<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected LoyaltyService $loyaltyService;

    public function __construct(PaymentService $paymentService, LoyaltyService $loyaltyService)
    {
        $this->paymentService = $paymentService;
        $this->loyaltyService = $loyaltyService;
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
            if ($booking->prepayment_amount <= 0) {
                return DB::transaction(function() use ($booking) {
                    $specialist = $booking->specialist;
                    $finalStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'paid_at' => now(),
                        'payment_reference' => 'FREE-DISCOUNT-' . $booking->id,
                        'payment_details' => [
                            'method' => 'full_discount',
                            'gateway_amount' => 0,
                            'discount_code' => $booking->discount_code
                        ]
                    ]);

                    try {
                        $this->loyaltyService->earnPointsFromBooking($booking->user_id, $booking->id);
                    } catch (\Exception $e) {
                        Log::warning('خطا در اعطای امتیاز وفاداری', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    return redirect()->route('bookings.success', ['id' => $booking->id])
                        ->with('success', 'نوبت شما با موفقیت ثبت شد (تخفیف کامل).');
                });
            }
            $result = $this->paymentService->createPayment($booking);

            if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
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
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'خطایی سیستمی در فرآیند پرداخت: ' . $e->getMessage());
        }
    }

    public function processWithWallet(Request $request, Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'دسترسی غیرمجاز');
            }

            if ($booking->payment_status === 'paid') {
                return redirect()->route('bookings.show', $booking)
                    ->with('info', 'این نوبت قبلاً پرداخت شده است.');
            }

            $validated = $request->validate([
                'use_wallet' => 'required|boolean',
                'wallet_amount' => 'nullable|numeric|min:0',
            ]);

            $user = auth()->user();
            $wallet = $user->getOrCreateWallet();
            $totalAmount = $booking->prepayment_amount;
            $walletAmount = min(
                $validated['wallet_amount'] ?? $wallet->balance,
                $wallet->balance,
                $totalAmount
            );
            $remainingAmount = $totalAmount - $walletAmount;

            return DB::transaction(function() use ($booking, $wallet, $walletAmount, $remainingAmount, $totalAmount) {
                if ($remainingAmount <= 0) {
                    $wallet->deductPayment(
                        $walletAmount,
                        $booking->id,
                        "پرداخت نوبت #{$booking->id} از کیف پول"
                    );

                    $specialist = $booking->specialist;
                    $finalStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';
                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'paid_at' => now(),
                        'payment_reference' => 'WALLET-' . $booking->id . '-' . time(),
                        'payment_details' => [
                            'method' => 'wallet',
                            'wallet_amount' => $walletAmount,
                            'gateway_amount' => 0
                        ]
                    ]);
                    try {
                        $this->loyaltyService->earnPointsFromBooking(auth()->id(), $booking->id);
                    } catch (\Exception $e) {
                        Log::warning('خطا در اعطای امتیاز وفاداری', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    return redirect()->route('bookings.success', ['id' => $booking->id])
                        ->with('success', 'پرداخت از کیف پول با موفقیت انجام شد');
                }

                if ($walletAmount > 0) {
                    $wallet->deductPayment(
                        $walletAmount,
                        $booking->id,
                        "پرداخت بخشی از نوبت #{$booking->id} از کیف پول (مابقی از درگاه)"
                    );

                    session([
                        'partial_payment_' . $booking->id => [
                            'wallet_amount' => $walletAmount,
                            'remaining_amount' => $remainingAmount
                        ]
                    ]);
                }

                $result = $this->paymentService->createPayment($booking, $remainingAmount);

                if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                    return redirect($result['payment_url']);
                }

                if ($walletAmount > 0) {
                    $wallet->increment('balance', $walletAmount);
                    $wallet->transactions()->create([
                        'booking_id' => $booking->id,
                        'type' => 'refund',
                        'amount' => $walletAmount,
                        'balance_after' => $wallet->balance,
                        'description' => "بازگشت وجه به دلیل خطا در اتصال به درگاه - نوبت #{$booking->id}",
                    ]);
                }

                throw new \Exception($result['message'] ?? 'خطا در اتصال به درگاه پرداخت');
            });

        } catch (\Exception $e) {
            Log::error('💥 خطا در پرداخت با کیف پول', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در پرداخت: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $result = $this->paymentService->verifyPayment($request);
            $booking = Booking::findOrFail($result['booking_id']);

            if ($result['success']) {
                $booking->refresh();

                if ($booking->payment_status !== 'paid') {
                    $specialist = $booking->specialist;
                    $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
                    $newStatus = $isAutoConfirm ? 'confirmed' : 'pending';

                    DB::transaction(function () use ($booking, $result, $newStatus, $specialist) {
                        $partialPayment = session('partial_payment_' . $booking->id);
                        $paymentDetails = [
                            'method' => $partialPayment ? 'wallet_gateway' : 'gateway',
                            'gateway_ref' => $result['ref_id'] ?? $result['reference'],
                            'card_pan' => $result['card_pan'] ?? null,
                        ];

                        if ($partialPayment) {
                            $paymentDetails['wallet_amount'] = $partialPayment['wallet_amount'];
                            $paymentDetails['gateway_amount'] = $partialPayment['remaining_amount'];
                            session()->forget('partial_payment_' . $booking->id);
                        }

                        $booking->update([
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                            'payment_reference' => $result['ref_id'] ?? $result['reference'],
                            'status' => $newStatus,
                            'payment_details' => $paymentDetails
                        ]);

                        $specialist->getOrCreateWallet()->addIncome(
                            $booking->prepayment_amount,
                            $booking->id
                        );
                        try {
                            $this->loyaltyService->earnPointsFromBooking($booking->user_id, $booking->id);
                        } catch (\Exception $e) {
                            Log::warning('⚠️ خطا در اعطای امتیاز وفاداری', [
                                'booking_id' => $booking->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    });
                }

                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.');
            }

            $partialPayment = session('partial_payment_' . $booking->id);
            if ($partialPayment && isset($partialPayment['wallet_amount'])) {
                $wallet = $booking->user->getOrCreateWallet();
                $wallet->increment('balance', $partialPayment['wallet_amount']);
                $wallet->transactions()->create([
                    'booking_id' => $booking->id,
                    'type' => 'refund',
                    'amount' => $partialPayment['wallet_amount'],
                    'balance_after' => $wallet->balance,
                    'description' => "بازگشت وجه به دلیل عدم موفقیت پرداخت - نوبت #{$booking->id}",
                ]);

                session()->forget('partial_payment_' . $booking->id);
            }

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

        $wallet = auth()->user()->getOrCreateWallet();

        return view('payment.show', [
            'booking' => $booking,
            'wallet' => $wallet
        ]);
    }
}
