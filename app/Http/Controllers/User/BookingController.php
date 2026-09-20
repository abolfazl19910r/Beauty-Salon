<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\RateBookingRequest;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Traits\HasJalaliDates;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingController extends Controller
{
    use HasJalaliDates;

    public function __construct(protected readonly BookingRepositoryInterface $bookingRepository) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $filters = [
            'status' => $request->filled('status') ? $request->status : null,
            'date' => null,
        ];

        if ($request->filled('date')) {
            if ($gregorianDate = $this->parseJalali($request->query('date'))) {
                $filters['date'] = $gregorianDate->toDateString();
            }
        }

        $bookings = $this->bookingRepository->paginateForUser($user->id, $filters, 10);

        return view('bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View|RedirectResponse
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
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('bookings.index')
                ->with('error', 'خطا در نمایش جزئیات نوبت.');
        }
    }

    public function success(Request $request): View
    {
        $bookingId = session('booking_id') ?? $request->query('id');

        $booking = null;

        if ($bookingId) {
            $booking = $this->bookingRepository->findForUserWithDetails((int) $bookingId, auth()->id());
        }

        return view('bookings.success', compact('booking'));
    }

    public function failed(Request $request): View
    {
        $booking = null;

        if ($bookingId = session('booking_id')) {
            $booking = $this->bookingRepository->findForUser((int) $bookingId, auth()->id());
        }

        $errorMessage = session('error') ?? 'متاسفانه پرداخت با خطا مواجه شد.';

        return view('bookings.failed', compact('booking', 'errorMessage'));
    }

    public function rate(RateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);

        try {
            $this->bookingRepository->update($booking, $request->validated());

            $booking->specialist->notify(new \App\Notifications\Review\NewReviewNotification($booking));

            auth()->user()->addLoyaltyPoints(10, 'ثبت نظر برای نوبت #'.$booking->id);

            return back()->with('success', 'نظر شما با موفقیت ثبت شد.');

        } catch (Exception $e) {
            return back()->with('error', 'خطا در ثبت نظر: '.$e->getMessage());
        }
    }

    public function getUserBookings(): Collection
    {
        return $this->bookingRepository->getAllForUser(auth()->id());
    }

    public function getUpcomingBookings(): JsonResponse
    {
        $bookings = $this->bookingRepository->getUpcomingExcludingCancelledForUser(Auth::id());

        return response()->json([
            'bookings' => $bookings,
            'count' => $bookings->count(),
        ]);
    }

    public function getPastBookings(): JsonResponse
    {
        $bookings = $this->bookingRepository->getPastForUserApi(Auth::id());

        return response()->json([
            'bookings' => $bookings,
            'count' => $bookings->count(),
        ]);
    }

    public function latestSuccessful(): JsonResponse
    {
        try {
            $booking = $this->bookingRepository->getLatestSuccessfulForUser(Auth::id());

            return response()->json($booking);

        } catch (Exception $e) {
            return response()->json(['error' => 'هیچ رزرو موفقی یافت نشد.'], 404);
        }
    }
}
