<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\ApplyDiscountRequest;
use App\Http\Requests\Booking\CheckDiscountRequest;
use App\Http\Requests\Booking\ConfirmBookingRequest;
use App\Http\Requests\Booking\RateBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Notifications\BookingNotification;
use App\Notifications\BookingStatusUpdated;
use App\Notifications\CustomerBookingNotification;
use App\Notifications\SpecialistBookingCancelledNotification;
use App\Services\PaymentService;
use App\Services\SMSService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected SMSService $smsService,
    ) {}

    public function create()
    {
        if (! auth()->check()) {
            return redirect()->route('login')
                ->with('message', 'برای رزرو نوبت ابتدا باید وارد شوید.');
        }

        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('bookings.create', compact('services', 'specialists'));
    }

    public function confirm(ConfirmBookingRequest $request)
    {
        try {
            $service = BeautyService::findOrFail($request->service_id);
            $specialist = Specialist::findOrFail($request->specialist_id);
            $bookingTime = $request->booking_time;

            $bookingDate = date('Y-m-d', strtotime($bookingTime));
            $bookingTimeOnly = date('H:i', strtotime($bookingTime));
            $availableSlots = $specialist->getAvailableSlots($bookingDate);

            if (! in_array($bookingTimeOnly, $availableSlots)) {
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
            return back()->with('error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
        }
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $bookingTime = $request->booking_time;
                $specialist = Specialist::find($request->specialist_id);
                $bookingDate = date('Y-m-d', strtotime($bookingTime));
                $bookingTimeOnly = date('H:i', strtotime($bookingTime));

                $availableSlots = $specialist->getAvailableSlots($bookingDate);

                if (! in_array($bookingTimeOnly, $availableSlots)) {
                    return back()->with('error', 'این زمان قبلاً رزرو شده است. لطفاً زمان دیگری انتخاب کنید.');
                }

                $prepaymentAmount = 50000;
                $discountAmount = 0;

                if ($request->filled('discount_code')) {
                    $discountCode = DiscountCode::where('code', $request->discount_code)->first();

                    if ($discountCode && $discountCode->isValid()) {
                        $discountAmount = $discountCode->type === 'percentage'
                            ? ($prepaymentAmount * $discountCode->amount / 100)
                            : $discountCode->amount;

                        $discountCode->increment('used_count');
                    }
                }

                $finalAmount = max(0, $prepaymentAmount - $discountAmount);

                $booking = Booking::create([
                    'service_id'      => $request->service_id,
                    'specialist_id'   => $request->specialist_id,
                    'user_id'         => auth()->id(),
                    'booking_time'    => $bookingTime,
                    'status'          => 'pending_payment',
                    'prepayment_amount' => $finalAmount,
                    'payment_status'  => 'unpaid',
                    'discount_code'   => $request->discount_code,
                    'discount_amount' => $discountAmount,
                ]);

                $booking->load(['service', 'specialist', 'user']);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'نوبت با موفقیت ثبت شد.',
                        'booking' => $booking,
                    ]);
                }

                return redirect()->route('payment.show', ['booking' => $booking->id]);
            });

        } catch (Exception $e) {
            return back()->with('error', 'خطا در ثبت رزرو. لطفاً دوباره تلاش کنید.')
                ->withInput();
        }
    }

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
            $date = $request->date;
            $query->whereDate('booking_time', $date);
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

            if ($booking->payment_status === 'unpaid' &&
                $booking->status === 'pending_payment' &&
                ! session()->has('from_payment_result')) {

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

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        try {
            $booking->update(['status' => 'cancelled']);

            $booking->user?->notify(new BookingStatusUpdated($booking));
            $booking->specialist?->notify(new SpecialistBookingCancelledNotification($booking));

            return back()->with('success', 'نوبت با موفقیت لغو شد.');

        } catch (Exception $e) {
            return back()->with('error', 'خطا در لغو نوبت: ' . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $booking = null;

        if ($bookingId = session('booking_id')) {
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

    public function checkDiscount(CheckDiscountRequest $request): JsonResponse
    {
        $discountCode = DiscountCode::where('code', $request->code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            return response()->json([
                'valid'   => false,
                'message' => 'کد تخفیف نامعتبر است یا منقضی شده.',
            ]);
        }

        $prepaymentAmount = 50000;
        $discountAmount = $discountCode->type === 'percentage'
            ? ($prepaymentAmount * $discountCode->amount / 100)
            : $discountCode->amount;

        return response()->json([
            'valid'           => true,
            'discount_amount' => $discountAmount,
            'final_amount'    => max(0, $prepaymentAmount - $discountAmount),
            'message'         => 'کد تخفیف معتبر است.',
        ]);
    }

    public function applyDiscount(ApplyDiscountRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $discountCode = DiscountCode::where('code', $request->code)->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            return back()->with('error', 'کد تخفیف نامعتبر است یا منقضی شده.');
        }

        if ($discountCode->user_id && $discountCode->user_id !== auth()->id()) {
            return back()->with('error', 'این کد تخفیف متعلق به شما نیست.');
        }

        DB::transaction(function () use ($booking, $discountCode) {
            $basePrepayment = 50000;
            $discountAmount = $discountCode->type === 'percentage'
                ? ($basePrepayment * $discountCode->amount / 100)
                : $discountCode->amount;

            $booking->update([
                'discount_code'   => $discountCode->code,
                'discount_amount' => $discountAmount,
                'prepayment_amount' => max(0, $basePrepayment - $discountAmount),
            ]);

            $discountCode->increment('used_count');
        });

        return back()->with('success', 'کد تخفیف با موفقیت اعمال شد.');
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

    // ----------------------------------------------------------------
    // متدهای API (استفاده‌شده در routes/api/user/bookings.php)
    // ----------------------------------------------------------------

    public function getUserBookings(): Collection
    {
        return Booking::with(['service', 'specialist'])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    /**
     * نوبت‌های آینده — API endpoint.
     * نام قدیمی: upcoming() — در API route به‌اشتباه getUpcomingBookings نام‌گذاری شده بود (فیکس شد).
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
     * نوبت‌های گذشته — API endpoint.
     * نام قدیمی: past() — در API route به‌اشتباه getPastBookings نام‌گذاری شده بود (فیکس شد).
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
