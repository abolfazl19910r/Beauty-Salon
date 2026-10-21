<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\LoyaltyService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected readonly PaymentService $paymentService,
        protected readonly LoyaltyService $loyaltyService,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly \App\Services\Payment\BookingPaymentStarter $bookingPayment,
    ) {}

    public function process(Booking $booking): RedirectResponse|\Illuminate\Http\Response
    {
        try {
            $this->authorize('pay', $booking);
            if ($notPayable = $this->notPayable($booking)) {
                return $notPayable;
            }
            if ($booking->payment_status === 'paid') {
                // کلید جدا، نه «success»: layout هر session('success') را به‌عنوان پیام نشان می‌داد («1» بالای صفحه)
                return redirect()->route('payment.result')->with(['payment_result_success' => true, 'payment_result_booking' => $booking]);
            }
            $result = $this->bookingPayment->start(auth()->user(), $booking, 0, $this->chosenGatewayId());

            if ($result['state'] === 'paid') {
                return redirect()->route('bookings.success', ['id' => $booking->id])->with('success', $result['message']);
            }

            if ($result['state'] === 'redirect') {
                return \App\Payments\GatewayRedirect::to($result['gateway']);
            }

            Log::error('❌ Payment Gateway Error', [
                'booking_id' => $booking->id,
                'error' => $result['message'],
            ]);

            return back()->with('error', 'خطای بانک: '.$result['message']);

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
            if ($notPayable = $this->notPayable($booking)) {
                return $notPayable;
            }

            $validated = $request->validate([
                'use_wallet' => 'required|boolean',
                'wallet_amount' => 'nullable|numeric|min:0',
            ]);

            $result = $this->bookingPayment->start(
                auth()->user(),
                $booking,
                (float) ($validated['wallet_amount'] ?? auth()->user()->getOrCreateWallet()->balance),
                $this->chosenGatewayId(),
            );

            if ($result['state'] === 'paid') {
                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'پرداخت از کیف پول با موفقیت انجام شد');
            }

            if ($result['state'] === 'redirect') {
                return \App\Payments\GatewayRedirect::to($result['gateway']);
            }

            throw new \Exception($result['message']);
        } catch (\Illuminate\Validation\ValidationException|\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('💥 خطا در پرداخت با کیف پول', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در پرداخت: '.$e->getMessage());
        }
    }

    /** نوبت لغوشده، گذشته یا پرداخت‌شده دیگر پرداخت‌شدنی نیست (۲۰۲۶-۱۰-۱۱). */
    private function notPayable(Booking $booking): ?RedirectResponse
    {
        if ($booking->payment_status === 'paid' || $booking->isPayable()) {
            return null;
        }

        return redirect()->route('bookings.show', $booking)->with('error', $booking->notPayableReason());
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

    public function callback(Request $request, \App\Services\Payment\BookingPaymentFinalizer $finalizer): RedirectResponse
    {
        try {
            $outcome = $finalizer->handle($request);

            if ($outcome['state'] === 'paid') {
                return redirect()->route('bookings.success', ['id' => $outcome['booking']->id])
                    ->with('success', $outcome['message']);
            }

            // تأیید درگاه نرسید و نوبت نگه داشته شد (۲۰۲۶-۱۰-۲۲): صفحه‌ی خود نوبت با پیام «در حال بررسی»
            if ($outcome['state'] === 'verifying') {
                return redirect()->route('bookings.show', ['booking' => $outcome['booking']])
                    ->with('info', $outcome['message']);
            }

            return redirect()->route('bookings.failed')
                ->with('booking_id', $outcome['booking']?->id)
                ->with('error', $outcome['message']);

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
        $success = (bool) session('payment_result_success', false);
        $booking = session('payment_result_booking');
        $error_message = session('payment_result_error');

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
        if ($notPayable = $this->notPayable($booking)) {
            return $notPayable;
        }

        $wallet = auth()->user()->getOrCreateWallet();

        return view('payment.show', [
            'booking' => $booking,
            'wallet' => $wallet,
            'gatewayOptions' => $this->paymentService->gatewayOptions($booking->prepayment_amount),
        ]);
    }
}
