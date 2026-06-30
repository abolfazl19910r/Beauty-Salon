<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\UpdateRescheduleRequest;
use App\Models\Booking;
use App\Notifications\BookingRescheduledNotification;
use App\Services\SMSService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingRescheduleController extends Controller
{
    public function __construct(
        protected SMSService $smsService,
    ) {}

    public function show(Booking $booking)
    {
        $this->authorize('reschedule', $booking);

        return view('bookings.reschedule', compact('booking'));
    }

    public function update(UpdateRescheduleRequest $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorize('reschedule', $booking);

        $bookingTime = $request->booking_time;
        $specialist = $booking->specialist;
        $bookingDate = date('Y-m-d', strtotime($bookingTime));
        $bookingTimeOnly = date('H:i', strtotime($bookingTime));

        $availableSlots = $specialist->getAvailableSlots($bookingDate);

        if (! in_array($bookingTimeOnly, $availableSlots)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'زمان انتخاب شده در دسترس نیست.',
                ], 409);
            }

            return back()->with('error', 'زمان انتخاب شده در دسترس نیست.');
        }

        try {
            DB::transaction(function () use ($booking, $bookingTime) {
                $oldTime = $booking->booking_time;
                $specialist = $booking->specialist;

                $newStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';

                $booking->update([
                    'booking_time' => $bookingTime,
                    'status'       => $newStatus,
                ]);

                $booking->specialist->notify(new BookingRescheduledNotification($booking, $oldTime));

                $statusText = $newStatus === 'confirmed'
                    ? 'و به‌صورت خودکار تایید شد'
                    : 'و منتظر تایید مجدد متخصص است';

                $message = sprintf(
                    'زمان نوبت شما با موفقیت از %s به %s تغییر یافت %s.',
                    verta($oldTime)->format('Y/m/d H:i'),
                    verta($bookingTime)->format('Y/m/d H:i'),
                    $statusText
                );

                $this->smsService->send($booking->user->phone, $message);
            });

            $successMessage = 'زمان نوبت با موفقیت تغییر یافت.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => $successMessage,
                    'redirect' => route('bookings.show', $booking),
                ]);
            }

            return redirect()->route('bookings.show', $booking)
                ->with('success', $successMessage);

        } catch (Exception $e) {
            Log::error('خطا در تغییر زمان نوبت', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
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
