<?php

namespace App\Http\Controllers\User;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\ConfirmBookingRequest;
use App\Http\Requests\User\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Booking\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingReservationController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function create(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('salon.login')
                ->with('message', 'برای رزرو نوبت ابتدا باید وارد شوید.');
        }

        // بسته‌ی «ح»، ۸: رزرو موقتاً متوقف — به‌جای جادوگر رزرو، پیام سالن
        $salon = app(\App\Support\CurrentSalon::class)->get();
        if ($salon?->isBookingPaused()) {
            return view('bookings.paused', ['salon' => $salon]);
        }

        $services = $this->beautyServiceRepository->all();
        $specialists = $this->specialistRepository->all();
        // بسته‌ی 5ه: بی درگاه، نوبت «بدون پیش‌پرداخت» ثبت می‌شود (BookingPaymentPolicy) — دیگر چیزی برای بستن یا هشدار نیست
        $onlinePaymentAvailable = true;
        $prepaymentMode = $this->bookingService->termsFor(1.0)->prepaymentRequired;

        return view('bookings.create', compact('services', 'specialists', 'onlinePaymentAvailable', 'prepaymentMode'));
    }

    public function confirm(ConfirmBookingRequest $request): View|RedirectResponse
    {
        if (app(\App\Support\CurrentSalon::class)->get()?->isBookingPaused()) {
            return redirect()->route('bookings.create');
        }

        if ($blocked = $this->blockedForMissingMerchant((int) $request->service_id, false)) {
            return $blocked;
        }

        try {
            $service = $this->beautyServiceRepository->findOrFail($request->service_id);
            $specialist = $this->specialistRepository->findOrFail($request->specialist_id);
            $bookingTime = $request->booking_time;

            if (! $this->bookingService->isTimeAvailable($specialist->id, $bookingTime)) {
                return back()->with('error', 'متأسفانه این زمان دیگر در دسترس نیست. لطفاً زمان دیگری انتخاب کنید.');
            }

            $calc = $this->bookingService->calculatePrepayment((float) $service->price);
            $prepaymentAmount = $calc['original_amount'];
            $prepaymentRequired = $prepaymentAmount > 0;

            // سقف نوبت مشتری (بسته‌ی 5ه) پیش از صفحه‌ی تأیید هم گفته می‌شود؛ ثبت نهایی دوباره با قفل می‌سنجد.
            // نوبت با پیش‌پرداخت فقط سقف روزانه دارد (۲۰۲۶-۱۰-۲۹).
            try {
                app(\App\Services\Booking\CustomerBookingLimits::class)
                    ->assertCanBook((int) auth()->id(), (string) $bookingTime, countActive: ! $prepaymentRequired);
            } catch (\App\Exceptions\BookingLimitException $e) {
                return back()->with('error', $e->getUserMessage());
            }
            // مشتری بدقول (بسته‌ی 5ه): چرا پیش‌پرداخت یا تأیید سالن
            $bookingNotice = $calc['prepayment_reason'] === 'no_show' ? \App\Services\Booking\NoShowRecord::customerNotice($prepaymentRequired) : null;

            session([
                'pending_booking' => [
                    'service_id' => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'booking_time' => $bookingTime,
                ],
            ]);

            return view('bookings.confirm', compact('service', 'specialist', 'bookingTime', 'prepaymentAmount', 'prepaymentRequired', 'bookingNotice'));

        } catch (Exception $e) {
            Log::error('خطا در تأیید نوبت', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
        }
    }

    public function store(StoreBookingRequest $request): JsonResponse|RedirectResponse
    {
        // ⭐ ۲۰۲۶-۰۹-۲۴: نوبتی که پیش‌پرداخت لازم داره در سالنی که هنوز درگاه نداره اصلاً ساخته نمی‌شه
        // (وگرنه یک نوبت «در انتظار پرداخت» می‌موند که هیچ‌وقت قابل پرداخت نیست).
        if ($blocked = $this->blockedForMissingMerchant((int) $request->service_id, $request->expectsJson())) {
            return $blocked;
        }

        try {
            $booking = $this->bookingService->createBooking(
                userId: auth()->id(),
                serviceId: $request->service_id,
                specialistId: $request->specialist_id,
                bookingTime: $request->booking_time,
                discountCode: $request->discount_code,
                idempotencyKey: $request->validated('idempotency_key'),
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'نوبت با موفقیت ثبت شد.',
                    'booking' => $booking,
                ]);
            }

            // چیزی برای پرداخت نیست (پیش‌پرداخت صفر): مستقیم صفحه‌ی نوبت، نه صفحه‌ی پرداخت
            if (! $booking->isPayable()) {
                return redirect()->route('bookings.show', ['booking' => $booking->id])
                    ->with('success', $booking->status === 'confirmed'
                        ? 'نوبت شما ثبت و تأیید شد؛ کل مبلغ را در سالن پرداخت می‌کنید.'
                        : 'نوبت شما ثبت شد و در انتظار تأیید سالن است؛ کل مبلغ را در سالن پرداخت می‌کنید.');
            }

            return redirect()->route('payment.show', ['booking' => $booking->id]);

        } catch (BookingNotAvailableException $e) {

            throw $e;
        } catch (\App\Exceptions\DiscountCodeInvalidException $e) {
            if ($request->expectsJson()) {
                throw $e;
            }

            return back()->with('error', $e->getUserMessage())->withInput();
        } catch (\App\Exceptions\BookingLimitException $e) {
            if ($request->expectsJson()) {
                throw $e;
            }

            return back()->with('error', $e->getUserMessage())->withInput();
        } catch (\App\Exceptions\IdempotencyKeyReusedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error('خطا در ثبت نوبت', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'خطا در ثبت رزرو. لطفاً دوباره تلاش کنید.')
                ->withInput();
        }
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        try {
            $this->bookingService->cancelBooking($booking);

            return back()->with('success', 'نوبت با موفقیت لغو شد.');

        } catch (Exception $e) {
            Log::error('خطا در لغو نوبت', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در لغو نوبت.');
        }
    }

    private function blockedForMissingMerchant(int $serviceId, bool $json): JsonResponse|RedirectResponse|null
    {
        $salon = app(\App\Support\CurrentSalon::class)->get();
        if (! $salon || $salon->acceptsOnlinePayments()) {
            return null;
        }

        $service = $this->beautyServiceRepository->find($serviceId);
        if (! $service || $this->bookingService->calculatePrepayment((float) $service->price)['original_amount'] <= 0) {
            return null;
        }

        return $json
            ? response()->json(['message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE], 422)
            : back()->with('error', \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE)->withInput();
    }
}
