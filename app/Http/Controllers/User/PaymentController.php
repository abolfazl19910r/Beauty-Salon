<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\LoyaltyService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected readonly PaymentService $paymentService,
        protected readonly LoyaltyService $loyaltyService,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function process(Booking $booking): RedirectResponse|\Illuminate\Http\Response
    {
        try {
            $this->authorize('pay', $booking);
            if ($booking->payment_status === 'paid') {
                return redirect()->route('payment.result')->with(['success' => true, 'booking' => $booking]);
            }
            if ($booking->prepayment_amount <= 0) {
                DB::transaction(function () use ($booking) {
                    $specialist = $booking->specialist;
                    $finalStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';
                    $this->bookingRepository->update($booking, [
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'paid_at' => now(),
                        'payment_reference' => 'FREE-DISCOUNT-'.$booking->id,
                        'payment_details' => [
                            'method' => 'full_discount',
                            'gateway_amount' => 0,
                            'discount_code' => $booking->discount_code,
                        ],
                    ]);

                });

                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'نوبت شما با تخفیف کامل با موفقیت ثبت شد.');
            }
            $result = $this->paymentService->createPayment($booking, null, $this->chosenGatewayId());

            if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                return \App\Payments\GatewayRedirect::to($result);
            }

            $errorMessage = $result['message'] ?? 'در حال حاضر امکان اتصال به درگاه بانکی وجود ندارد.';
            Log::error('❌ Payment Gateway Error', [
                'booking_id' => $booking->id,
                'error' => $errorMessage,
                'full_result' => $result,
            ]);

            return back()->with('error', 'خطای بانک: '.$errorMessage);

        } catch (\Exception $e) {
            Log::error('💥 Payment Process Exception', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'خطایی سیستمی در فرآیند پرداخت: '.$e->getMessage());
        }
    }

    public function processWithWallet(Request $request, Booking $booking): RedirectResponse|\Illuminate\Http\Response
    {
        try {
            $this->authorize('pay', $booking);

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

            $paidFully = false;

            $response = DB::transaction(function () use ($booking, $wallet, $walletAmount, $remainingAmount, &$paidFully) {
                if ($remainingAmount <= 0) {
                    $wallet->deductPayment(
                        $walletAmount,
                        $booking->id,
                        "پرداخت نوبت #{$booking->id} از کیف پول"
                    );

                    $specialist = $booking->specialist;
                    $finalStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';
                    $this->bookingRepository->update($booking, [
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'paid_at' => now(),
                        'payment_reference' => 'WALLET-'.$booking->id.'-'.time(),
                        'payment_details' => [
                            'method' => 'wallet',
                            'wallet_amount' => $walletAmount,
                            'gateway_amount' => 0,
                        ],
                    ]);
                    $paidFully = true;

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
                        'partial_payment_'.$booking->id => [
                            'wallet_amount' => $walletAmount,
                            'remaining_amount' => $remainingAmount,
                        ],
                    ]);
                }

                $result = $this->paymentService->createPayment($booking, $remainingAmount, $this->chosenGatewayId());

                if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                    return \App\Payments\GatewayRedirect::to($result);
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

            return $response;

        } catch (\Exception $e) {
            Log::error('💥 خطا در پرداخت با کیف پول', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در پرداخت: '.$e->getMessage());
        }
    }

    /**
     * ⭐ مرحله‌ی ۱ چند درگاه: درگاهی که مشتری در صفحه‌ی پرداخت انتخاب کرده. فقط یک ترجیح است — GatewayManager
     * فقط درگاه‌های فعال همین سالن رو قبول می‌کنه و اگه این یکی در دسترس نبود، خودکار سراغ بعدی می‌ره.
     */
    private function chosenGatewayId(): ?int
    {
        $id = request()->input('gateway_id');

        return is_numeric($id) ? (int) $id : null;
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $result = $this->paymentService->verifyPayment($request);
            $booking = $this->bookingRepository->findOrFail($result['booking_id']);

            if ($result['success']) {
                $booking->refresh();

                if ($booking->payment_status !== 'paid') {
                    $specialist = $booking->specialist;
                    $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
                    $newStatus = $isAutoConfirm ? 'confirmed' : 'pending';
                    $partialPayment = session('partial_payment_'.$booking->id);

                    try {
                        DB::transaction(function () use ($booking, $result, $newStatus, $partialPayment) {
                            $paymentDetails = [
                                'method' => $partialPayment ? 'wallet_gateway' : 'gateway',
                                'gateway_ref' => $result['ref_id'] ?? $result['reference'],
                                'card_pan' => $result['card_pan'] ?? null,
                                'gateway' => $result['gateway'] ?? null,
                                'gateway_fee' => $result['gateway_fee'] ?? 0,
                            ];

                            if ($partialPayment) {
                                $paymentDetails['wallet_amount'] = $partialPayment['wallet_amount'];
                                $paymentDetails['gateway_amount'] = $partialPayment['remaining_amount'];
                                session()->forget('partial_payment_'.$booking->id);
                            }

                            $this->bookingRepository->update($booking, [
                                'payment_status' => 'paid',
                                'paid_at' => now(),
                                'payment_reference' => $result['ref_id'] ?? $result['reference'],
                                'status' => $newStatus,
                                'payment_details' => $paymentDetails,
                            ]);
                        });
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        // ⭐ درگاه پول رو گرفته ولی ساعت این نوبت (که در این فاصله لغو شده بود) حالا مال نفر دیگه‌ایه —
                        // index یکتای bookings.active_slot_key. پول به کارت (سامان) یا کیف پول برمی‌گرده؛ هرگز «ناموفق» بی‌بازگشت.
                        if (! str_contains($e->getMessage(), 'active_slot')) {
                            throw $e;
                        }

                        $refund = app(\App\Services\Payment\LostSlotRefundService::class)->refund(
                            $booking->fresh(),
                            isset($result['transaction_id']) ? \App\Models\PaymentTransaction::find($result['transaction_id']) : null,
                            $partialPayment,
                            $partialPayment['remaining_amount'] ?? $booking->prepayment_amount,
                        );
                        session()->forget('partial_payment_'.$booking->id);

                        return redirect()->route('bookings.failed')->with('error', $refund['handled']
                            ? \App\Services\Payment\LostSlotRefundService::message($refund)
                            : 'مبلغ این پرداخت قبلاً به شما برگشت داده شده است و نوبت ثبت نشد.');
                    }
                }

                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.');
            }

            if (! empty($result['refunded'])) {
                // پول قبلاً برگشت داده شده؛ نوبت و کیف پول دست نمی‌خورن (بخش کیف پولی همون موقع برگشت خورده)
                session()->forget('partial_payment_'.$booking->id);

                return redirect()->route('bookings.failed')->with('error', $result['message']);
            }

            $partialPayment = session('partial_payment_'.$booking->id);
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

                session()->forget('partial_payment_'.$booking->id);
            }

            $this->bookingRepository->update($booking, ['status' => 'cancelled', 'cancellation_reason' => 'پرداخت ناموفق']);

            return redirect()->route('bookings.failed')
                ->with('error', $result['message'] ?? 'پرداخت ناموفق بود');

        } catch (\Exception $e) {
            Log::error('💥 Payment Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('bookings.failed')
                ->with('error', 'خطا در تایید تراکنش');
        }
    }

    public function result(): View|RedirectResponse
    {
        $success = session('success', false);
        $booking = session('booking');
        $error_message = session('error_message');

        if (! $booking) {
            return redirect()->route('home');
        }

        return view('payment.result', compact('success', 'booking', 'error_message'));
    }

    public function show(Booking $booking): View|RedirectResponse
    {
        $this->authorize('pay', $booking);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', ['booking' => $booking])
                ->with('info', 'این نوبت قبلاً پرداخت شده است.');
        }

        $wallet = auth()->user()->getOrCreateWallet();

        return view('payment.show', [
            'booking' => $booking,
            'wallet' => $wallet,
            'gatewayOptions' => $this->paymentService->gatewayOptions($booking->prepayment_amount),
        ]);
    }
}
