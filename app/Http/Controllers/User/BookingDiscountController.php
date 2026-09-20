<?php

namespace App\Http\Controllers\User;

use App\Exceptions\DiscountCodeInvalidException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\ApplyDiscountRequest;
use App\Http\Requests\User\Booking\CheckDiscountRequest;
use App\Models\Booking;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\BookingService;
use App\Traits\HandlesApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class BookingDiscountController extends Controller
{
    use HandlesApiResponse;

    public function __construct(
        protected BookingService $bookingService,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
    ) {}

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

    private function resolveBaseAmount(CheckDiscountRequest $request): float
    {
        if ($request->filled('booking_id')) {
            $booking = $this->bookingRepository->findOrFail($request->integer('booking_id'));

            $this->authorize('view', $booking);

            return (float) $booking->prepayment_amount;
        }

        if ($request->filled('service_id')) {
            $service = $this->beautyServiceRepository->findOrFail($request->integer('service_id'));

            return $this->bookingService->calculatePrepayment((float) $service->price)['original_amount'];
        }

        return 50000.0;
    }
}
