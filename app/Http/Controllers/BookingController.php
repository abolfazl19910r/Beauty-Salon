<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\RateBookingRequest;
use App\Models\Booking;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Responsible for displaying the list and details of turns, payment success/failure pages, and comment registration.
 * Methods extracted to separate controllers (phase R3):
 *  - create / confirm / store / cancel  → BookingReservationController
 *  - checkDiscount / applyDiscount      → BookingDiscountController
 *  - getAvailableTimeSlots / Dates / …  → BookingAvailabilityController
 *  - show / update (reschedule)         → BookingRescheduleController
 */
class BookingController extends Controller
{
    // ── Web ──────────────────────────────────────────────────────────

    public function index(Request $request): \Illuminate\View\View
    {
        $user = auth()->user();

        $query = Booking::with(['service', 'specialist'])
            ->where('user_id', $user->id)
            ->orderBy('booking_time', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            try {
                $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
                $dateInput = str_replace($persianDigits, $englishDigits, $request->query('date'));
                $gregorianDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateInput)
                    ->toCarbon()
                    ->toDateString();
                $query->whereDate('booking_time', $gregorianDate);
            } catch (\Exception $e) {
                // Invalid date — filter not applied
            }
        }

        $bookings = $query->paginate(10)->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        try {
            $booking->load(['service', 'specialist']);

            if (! $booking->service) {
                return redirect()->route('bookings.index')
                    ->with('error', 'اطلاعات سرویس برای این نوبت یافت نشد.');
            }

            if (
                $booking->payment_status === 'unpaid' &&
                $booking->status === 'pending_payment' &&
                ! session()->has('from_payment_result')
            ) {
                return redirect()->route('payment.show', ['booking' => $booking->id])
                    ->with('info', 'لطفاً ابتدا پرداخت را تکمیل کنید.');
            }

            session()->forget('from_payment_result');

            return view('bookings.show', compact('booking'));

        } catch (Exception $e) {
            Log::error('خطا در نمایش جزئیات نوبت', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('bookings.index')
                ->with('error', 'خطا در نمایش جزئیات نوبت.');
        }
    }

    public function success(Request $request)
    {
        // PaymentController redirects with ?id=, so we check both
        $bookingId = session('booking_id') ?? $request->query('id');

        $booking = null;

        if ($bookingId) {
            $booking = Booking::with(['service', 'specialist'])
                ->where('id', $bookingId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('bookings.success', compact('booking'));
    }

    public function failed(Request $request)
    {
        $booking = null;

        if ($bookingId = session('booking_id')) {
            $booking = Booking::where('id', $bookingId)
                ->where('user_id', auth()->id())
                ->first();
        }

        $errorMessage = session('error') ?? 'متاسفانه پرداخت با خطا مواجه شد.';

        return view('bookings.failed', compact('booking', 'errorMessage'));
    }

    public function rate(RateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);

        try {
            $booking->update($request->validated());

            $booking->specialist->notify(new \App\Notifications\NewReviewNotification($booking));

            auth()->user()->addLoyaltyPoints(10, 'ثبت نظر برای نوبت #' . $booking->id);

            return back()->with('success', 'نظر شما با موفقیت ثبت شد.');

        } catch (Exception $e) {
            return back()->with('error', 'خطا در ثبت نظر: ' . $e->getMessage());
        }
    }

    // ── API ──────────────────────────────────────────────────────────

    public function getUserBookings(): Collection
    {
        return Booking::with(['service', 'specialist'])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    /**
     * Upcoming Turns — API endpoint.
     * * Old name in controller: upcoming() — fixed to be consistent with route.
 */
    public function getUpcomingBookings(): JsonResponse
    {
        $bookings = Booking::with(['service', 'specialist'])
            ->where('user_id', Auth::id())
            ->where('booking_time', '>', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('booking_time', 'asc')
            ->get();

        return response()->json([
            'bookings' => $bookings,
            'count'    => $bookings->count(),
        ]);
    }

    /**
     * Past Turns — API endpoint.
     * * Old name in controller: past() — fixed to be consistent with route.
 */
    public function getPastBookings(): JsonResponse
    {
        $bookings = Booking::with(['service', 'specialist'])
            ->where('user_id', Auth::id())
            ->where('booking_time', '<=', now())
            ->orderBy('booking_time', 'desc')
            ->get();

        return response()->json([
            'bookings' => $bookings,
            'count'    => $bookings->count(),
        ]);
    }

    public function latestSuccessful(): JsonResponse
    {
        try {
            $booking = Booking::with(['service', 'specialist'])
                ->where('user_id', Auth::id())
                ->where('payment_status', 'paid')
                ->latest('paid_at')
                ->firstOrFail();

            return response()->json($booking);

        } catch (Exception $e) {
            return response()->json(['error' => 'هیچ رزرو موفقی یافت نشد.'], 404);
        }
    }
}
