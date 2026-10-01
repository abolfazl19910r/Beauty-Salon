<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\UpdateRescheduleRequest;
use App\Models\Booking;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\SMSService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingRescheduleController extends Controller
{
    public function __construct(
        protected SMSService $smsService,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function show(Booking $booking): View
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
                // کل مدت خدمت با نوبت‌های دیگر، استراحت و ساعت کاری؛ با قفل ردیف متخصص (۲۰۲۶-۱۰-۰۱)
                app(\App\Services\Booking\BookingService::class)
                    ->assertBookingFits((int) $booking->specialist_id, $bookingTime, (int) $booking->service_id, $booking->id);

                $oldTime = $booking->booking_time;
                $specialist = $booking->specialist;

                $newStatus = $specialist->auto_confirm_bookings ? 'confirmed' : 'pending';

                $this->bookingRepository->update($booking, [
                    'booking_time' => $bookingTime,
                    'status' => $newStatus,
                ]);

                $booking->specialist->notify(new BookingRescheduledNotification($booking, $oldTime));

                $message = \App\Support\Sms\SmsText::rescheduledForCustomer($booking->fresh(['service']), $newStatus !== 'confirmed');
                app(\App\Services\Bot\BotMessenger::class)->send($booking->user, $message, \App\Support\Notifications\NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER, $booking->salon_id);
                $this->smsService->send($booking->user->phone, $message, $booking->salon_id);
            });

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
