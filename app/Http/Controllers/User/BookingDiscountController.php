<?php

namespace App\Http\Controllers\User;

use App\Exceptions\DiscountCodeInvalidException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\ApplyDiscountRequest;
use App\Http\Requests\User\Booking\CheckDiscountRequest;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Traits\HandlesApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * ⚠️ Fixed critical bug (R-DiscountLogic): This controller previously imported
 * `use App\Http\Requests\Booking\ApplyDiscountRequest;` —
 * a class that didn't exist at all (the entire namespace had been moved from Requests/Booking to
 * Requests/User/Booking). This meant that every time the apply() method was called
 * (whether from the Blade form of the appointment details page or from anywhere else), a fatal error
 * "Class not found" would occur — the "Apply Discount Code" web path was effectively broken from the start.
 */
class BookingDiscountController extends Controller
{
    use HandlesApiResponse;

    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Preview (no persist). Used for both web and API.
     */
    public function check(CheckDiscountRequest $request): JsonResponse
    {
        try {
            $result = $this->bookingService->validateDiscountCode(
                code: $request->code,
                userId: auth()->id(),
                baseAmount: $this->resolveBaseAmount($request),
            );

            return response()->json($result);

        } catch (DiscountCodeInvalidException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('خطا در بررسی کد تخفیف', [
                'code' => $request->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['valid' => false, 'message' => 'خطا در بررسی کد تخفیف.'], 500);
        }
    }

    /**
     * Actual actions (persist) — for web forms (RedirectResponse) as well as
     * * API consumers that send an Accept: application/json header (JsonResponse).
     */
    public function apply(ApplyDiscountRequest $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $this->authorize('applyDiscount', $booking);

        try {
            $result = $this->bookingService->applyDiscountCode($booking, $request->code);

            if ($request->expectsJson()) {
                return response()->json($result, $result['success'] ? 200 : 422);
            }

            if (! $result['success']) {
                return back()->with('error', $result['message']);
            }

            return back()->with('success', sprintf(
                'کد تخفیف اعمال شد. %s تومان از باقی‌مانده‌ای که موقع نوبت پرداخت می‌کنید کسر شد.',
                number_format($result['discount_amount'])
            ));

        } catch (Exception $e) {
            Log::error('خطا در اعمال کد تخفیف', [
                'booking_id' => $booking->id,
                'code' => $request->code,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return $this->errorResponse('خطا در اعمال کد تخفیف.', 500);
            }

            return back()->with('error', 'خطا در اعمال کد تخفیف.');
        }
    }

    /**
     * Guaranteed JSON version for API routes (e.g. future mobile app) where possible
     * * Do not always send the Accept: application/json header correctly.
     */
    public function applyApi(ApplyDiscountRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('applyDiscount', $booking);

        try {
            $result = $this->bookingService->applyDiscountCode($booking, $request->code);

            return response()->json($result, $result['success'] ? 200 : 422);

        } catch (Exception $e) {
            Log::error('خطا در اعمال کد تخفیف (API)', [
                'booking_id' => $booking->id,
                'code' => $request->code,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('خطا در اعمال کد تخفیف.', 500);
        }
    }

    /**
     * Actual base amount for discount preview: prepayment of an existing appointment,
     * * Calculated prepayment of a service, or minimum default prepayment.
     * * Previously this number was always hardcoded to 50,000 regardless of what was actually requested.
     */
    private function resolveBaseAmount(CheckDiscountRequest $request): float
    {
        if ($request->filled('booking_id')) {
            $booking = Booking::findOrFail($request->integer('booking_id'));

            $this->authorize('view', $booking);

            return (float) $booking->prepayment_amount;
        }

        if ($request->filled('service_id')) {
            $service = BeautyService::findOrFail($request->integer('service_id'));

            return $this->bookingService->calculatePrepayment((float) $service->price)['original_amount'];
        }

        return 50000.0;
    }
}
