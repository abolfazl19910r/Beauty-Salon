<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\UpdateRescheduleRequest;
use App\Models\Booking;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingRescheduleController extends Controller
{
    public function show(Booking $booking): View
    {
        $this->authorize('reschedule', $booking);

        return view('bookings.reschedule', compact('booking'));
    }

    public function update(UpdateRescheduleRequest $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorize('reschedule', $booking);

        try {
            app(\App\Services\Booking\BookingRescheduleService::class)->reschedule($booking, $request->booking_time);

            $successMessage = 'زمان نوبت با موفقیت تغییر یافت.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route('bookings.show', $booking),
                ]);
            }

            return redirect()->route('bookings.show', $booking)
                ->with('success', $successMessage);

        } catch (\App\Exceptions\BookingNotAvailableException) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'زمان انتخاب شده در دسترس نیست.'], 409);
            }

            return back()->with('error', 'زمان انتخاب شده در دسترس نیست.');
        } catch (Exception $e) {
            Log::error('خطا در تغییر زمان نوبت', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در تغییر زمان نوبت.',
                ], 500);
            }

            return back()->with('error', 'خطا در تغییر زمان نوبت.');
        }
    }
}
