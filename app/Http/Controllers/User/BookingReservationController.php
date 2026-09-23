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
            return redirect()->route('login')
                ->with('message', 'برای رزرو نوبت ابتدا باید وارد شوید.');
        }

        $services = $this->beautyServiceRepository->all();
        $specialists = $this->specialistRepository->all();
        // ⭐ ۲۰۲۶-۰۹-۲۴: سالن بدون درگاه زرین‌پال → بنر هشدار و غیرفعال شدن ثبت نوبت‌های نیازمند پیش‌پرداخت.
        $onlinePaymentAvailable = (bool) app(\App\Support\CurrentSalon::class)->get()?->acceptsOnlinePayments();

        return view('bookings.create', compact('services', 'specialists', 'onlinePaymentAvailable'));
    }

    public function confirm(ConfirmBookingRequest $request): View|RedirectResponse
    {
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

            $prepaymentAmount = $this->bookingService->calculatePrepayment((float) $service->price)['original_amount'];

            session([
                'pending_booking' => [
                    'service_id' => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'booking_time' => $bookingTime,
                ],
            ]);

            return view('bookings.confirm', compact('service', 'specialist', 'bookingTime', 'prepaymentAmount'));

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
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'نوبت با موفقیت ثبت شد.',
                    'booking' => $booking,
                ]);
            }

            return redirect()->route('payment.show', ['booking' => $booking->id]);

        } catch (BookingNotAvailableException $e) {

            throw $e;
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
