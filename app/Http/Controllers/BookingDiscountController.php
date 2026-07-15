<?php

namespace App\Http\Controllers;

use App\Exceptions\DiscountCodeInvalidException;
use App\Http\Requests\Booking\ApplyDiscountRequest;
use App\Http\Requests\User\Booking\CheckDiscountRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class BookingDiscountController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function check(CheckDiscountRequest $request): JsonResponse
    {
        try {
            $result = $this->bookingService->validateDiscountCode(
                code: $request->code,
                userId: auth()->id(),
            );

            return response()->json($result);

        } catch (DiscountCodeInvalidException $e) {

            throw $e;

        } catch (Exception $e) {
            Log::error('خطا در بررسی کد تخفیف', [
                'code'  => $request->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['valid' => false, 'message' => 'خطا در بررسی کد تخفیف.'], 500);
        }
    }

    public function apply(ApplyDiscountRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        try {
            $result = $this->bookingService->applyDiscountCode($booking, $request->code);

            if (! $result['success']) {
                return back()->with('error', $result['message']);
            }

            return back()->with('success', sprintf(
                'کد تخفیف اعمال شد. مبلغ قابل پرداخت: %s تومان',
                number_format($result['final_amount'])
            ));

        } catch (Exception $e) {
            Log::error('خطا در اعمال کد تخفیف', [
                'booking_id' => $booking->id,
                'code'       => $request->code,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در اعمال کد تخفیف.');
        }
    }
}
