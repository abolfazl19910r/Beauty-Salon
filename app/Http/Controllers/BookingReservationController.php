<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Requests\Booking\ConfirmBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class BookingReservationController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function create()
    {
        if (! auth()->check()) {
            return redirect()->route('login')
                ->with('message', 'برای رزرو نوبت ابتدا باید وارد شوید.');
        }

        $services = \App\Models\BeautyService::all();
        $specialists = \App\Models\Specialist::all();

        return view('bookings.create', compact('services', 'specialists'));
    }

    public function confirm(ConfirmBookingRequest $request)
    {
        try {
            $service = \App\Models\BeautyService::findOrFail($request->service_id);
            $specialist = \App\Models\Specialist::findOrFail($request->specialist_id);
            $bookingTime = $request->booking_time;

            if (! $this->bookingService->isTimeAvailable($specialist->id, $bookingTime)) {
                return back()->with('error', 'متأسفانه این زمان دیگر در دسترس نیست. لطفاً زمان دیگری انتخاب کنید.');
            }

            $prepaymentAmount = 50000;

            session([
                'pending_booking' => [
                    'service_id'    => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'booking_time'  => $bookingTime,
                ],
            ]);

            return view('bookings.confirm', compact('service', 'specialist', 'bookingTime', 'prepaymentAmount'));

        } catch (Exception $e) {
            Log::error('خطا در تأیید نوبت', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
        }
    }

    public function store(StoreBookingRequest $request)
    {
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
}
