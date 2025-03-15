<?php

namespace App\Http\Controllers;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Notifications\BookingNotification;
use App\Notifications\BookingRescheduledNotification;
use App\Notifications\CustomerBookingNotification;
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
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    protected PaymentService $paymentService;
    protected SMSService $smsService;

    public function __construct(PaymentService $paymentService, SMSService $smsService)
    {
        $this->paymentService = $paymentService;
        $this->smsService = $smsService;
    }

    public function create()
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('message', 'برای رزرو نوبت ابتدا باید وارد شوید.');
        }

        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('bookings.create', compact('services', 'specialists'));
    }

    public function confirm(Request $request)
    {
        try {
            $validated = $request->validate([
                'service_id' => 'required|exists:beauty_services,id',
                'specialist_id' => 'required|exists:specialists,id',
                'booking_time' => 'required|date|after:now',
            ]);

            $service = BeautyService::findOrFail($request->service_id);
            $specialist = Specialist::findOrFail($request->specialist_id);
            $bookingTime = $request->booking_time;

            $bookingDate = date('Y-m-d', strtotime($bookingTime));
            $bookingTimeOnly = date('H:i', strtotime($bookingTime));
            $availableSlots = $specialist->getAvailableSlots($bookingDate);

            if (!in_array($bookingTimeOnly, $availableSlots)) {
                return back()->with('error', 'متأسفانه این زمان دیگر در دسترس نیست. لطفاً زمان دیگری انتخاب کنید.');
            }

            $prepaymentAmount = 50000;

            return view('bookings.confirm', compact('service', 'specialist', 'bookingTime', 'prepaymentAmount'));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            return back()->with('error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
        }
    }

    public function checkDiscount(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
                'service_id' => 'required|exists:beauty_services,id'
            ]);

            $discountCode = DiscountCode::where('code', $request->code)->first();

            if (!$discountCode || !$discountCode->isValid()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'کد تخفیف نامعتبر است.'
                ], 200);
            }

            $service = BeautyService::find($request->service_id);
            $prepaymentAmount = 50000;

            $discountAmount = $discountCode->type === 'percentage'
                ? ($prepaymentAmount * $discountCode->amount / 100)
                : $discountCode->amount;

            $finalPrice = max(0, $prepaymentAmount - $discountAmount);

            return response()->json([
                'valid' => true,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'message' => 'کد تخفیف معتبر است.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'خطا در بررسی کد تخفیف.'
            ], 500);
        }
    }

    public function getAvailableTimeSlots(Specialist $specialist, $date)
    {
        try {
            $carbonDate = Carbon::parse($date);
            $dayOfWeek = $carbonDate->dayOfWeek;

            if ($specialist->holidays()->whereDate('date', $date)->exists()) {
                return response()->json([
                    'slots' => [],
                    'message' => 'این روز تعطیل است'
                ]);
            }

            if ($specialist->leaves()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists()) {
                return response()->json([
                    'slots' => [],
                    'message' => 'متخصص در این روز مرخصی است'
                ]);
            }

            $schedule = $specialist->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'slots' => [],
                    'message' => 'این روز جزو روزهای کاری متخصص نیست'
                ]);
            }

            $availableSlots = $specialist->getAvailableSlots($date);

            return response()->json([
                'slots' => $availableSlots,
                'schedule' => [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start ?? null,
                    'break_end' => $schedule->break_end ?? null
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'خطا در دریافت ساعت‌های در دسترس',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'برای رزرو نوبت ابتدا باید وارد سیستم شوید.'
            ], 401);
        }

        $validated = $request->validate([
            'service_id' => 'required|exists:beauty_services,id',
            'specialist_id' => 'required|exists:specialists,id',
            'booking_time' => 'required|date|after:now',
            'discount_code' => 'nullable|string'
        ]);

        try {
            return DB::transaction(function() use ($request) {
                $bookingTime = $request->booking_time;
                $specialist = Specialist::find($request->specialist_id);
                $bookingDate = date('Y-m-d', strtotime($bookingTime));
                $bookingTimeOnly = date('H:i', strtotime($bookingTime));

                $availableSlots = $specialist->getAvailableSlots($bookingDate);

                if (!in_array($bookingTimeOnly, $availableSlots)) {
                    throw ValidationException::withMessages([
                        'booking_time' => ['این زمان قبلاً رزرو شده است.']
                    ]);
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
                    'service_id' => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'user_id' => auth()->id(),
                    'booking_time' => $bookingTime,
                    'status' => 'pending',
                    'prepayment_amount' => $finalAmount,
                    'payment_status' => 'unpaid',
                    'discount_code' => $request->discount_code,
                    'discount_amount' => $discountAmount
                ]);

                $booking->load(['service', 'specialist']);

                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'نوبت با موفقیت ثبت شد.',
                        'booking' => $booking
                    ]);
                }

                return redirect()->route('payment.show', ['booking' => $booking->id]);
            });

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            return back()->with('error', 'خطا در ثبت رزرو. لطفا دوباره تلاش کنید.')
                ->withInput();
        }
    }

    public function processPayment(Booking $booking)
    {
        try {
            $payment = new PaymentService();
            $result = $payment->pay([
                'amount' => $booking->prepayment_amount,
                'callback_url' => route('payment.callback', $booking),
                'description' => 'پیش پرداخت نوبت سالن زیبایی'
            ]);

            $booking->update([
                'payment_ref' => $result['ref_id']
            ]);

            return redirect($result['payment_url']);

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در اتصال به درگاه پرداخت');
        }
    }

    public function paymentCallback(Request $request, Booking $booking)
    {
        try {
            $payment = new PaymentService();
            $result = $payment->verify($request->all());

            if ($result['status'] === 'success') {
                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed'
                ]);

                $booking->specialist->notify(new BookingNotification($booking));
                $booking->user->notify(new CustomerBookingNotification($booking));

                return redirect()->route('bookings.success')
                    ->with('success', 'رزرو با موفقیت انجام شد.');
            }

            return redirect()->route('bookings.failed')
                ->with('error', 'پرداخت ناموفق بود.');

        } catch (\Exception $e) {
            return redirect()->route('bookings.failed')
                ->with('error', 'خطا در تایید پرداخت');
        }
    }

    public function reschedule(Request $request, Booking $booking)
    {
        if (!$booking->canBeRescheduled()) {
            return back()->with('error', 'امکان تغییر زمان این نوبت وجود ندارد.');
        }

        $validated = $request->validate([
            'booking_time' => 'required|date|after:now'
        ]);

        $booking->update([
            'booking_time' => $validated['booking_time']
        ]);

        $smsService = new SMSService();
        $message = sprintf(
            'زمان نوبت شما به %s تغییر کرد.',
            verta($booking->booking_time)->format('Y/m/d H:i')
        );
        $smsService->send($booking->user->phone, $message);

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'زمان نوبت با موفقیت تغییر کرد.');
    }

    public function cancel(Booking $booking)
    {
        if (!$booking->canBeCancelled()) {
            return back()->with('error', 'امکان لغو این نوبت وجود ندارد.');
        }

        $booking->update(['status' => 'cancelled']);

        $smsService = new SMSService();
        $message = sprintf(
            'نوبت شما در تاریخ %s لغو شد.',
            verta($booking->booking_time)->format('Y/m/d H:i')
        );
        $smsService->send($booking->user->phone, $message);

        return redirect()->route('bookings.index')
            ->with('success', 'نوبت با موفقیت لغو شد.');
    }

    public function applyDiscount(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $discountCode = DiscountCode::where('code', $request->code)->first();

        if (!$discountCode || !$discountCode->isValid()) {
            return back()->with('error', 'کد تخفیف نامعتبر است.');
        }

        $discountAmount = $discountCode->type === 'percentage'
            ? ($booking->prepayment_amount * $discountCode->amount / 100)
            : $discountCode->amount;

        $booking->update([
            'discount_code' => $discountCode->code,
            'discount_amount' => $discountAmount,
            'prepayment_amount' => $booking->prepayment_amount - $discountAmount
        ]);

        $discountCode->increment('used_count');

        return back()->with('success', 'کد تخفیف با موفقیت اعمال شد.');
    }

    public function rate(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:500'
        ]);

        $booking->update($validated);

        return back()->with('success', 'نظر شما با موفقیت ثبت شد.');
    }

    public function getNextAvailableSlots($specialist_id, $count = 5): \Illuminate\Support\Collection
    {
        $specialist = Specialist::findOrFail($specialist_id);
        $nextDays = collect();
        $date = now();

        while ($nextDays->count() < $count) {
            $slots = $specialist->getAvailableSlots($date->format('Y-m-d'));
            if (!empty($slots)) {
                $nextDays->push([
                    'date' => $date->format('Y-m-d'),
                    'slots' => $slots
                ]);
            }
            $date->addDay();
        }

        return $nextDays;
    }

    public function getMonthlyAvailability($specialist_id, $year_month): array
    {
        $specialist = Specialist::findOrFail($specialist_id);
        $date = Carbon::createFromFormat('Y-m', $year_month);
        $daysInMonth = $date->daysInMonth;

        $availability = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $date->copy()->setDay($day);
            $slots = $specialist->getAvailableSlots($currentDate->format('Y-m-d'));

            $availability[] = [
                'date' => $currentDate->format('Y-m-d'),
                'has_slots' => !empty($slots),
                'slots_count' => count($slots),
                'is_holiday' => $specialist->holidays()
                    ->whereDate('date', $currentDate)
                    ->exists(),
                'is_leave' => $specialist->leaves()
                    ->whereDate('start_date', '<=', $currentDate)
                    ->whereDate('end_date', '>=', $currentDate)
                    ->where('status', 'approved')
                    ->exists()
            ];
        }

        return $availability;
    }

    public function getUserBookings(): \Illuminate\Database\Eloquent\Collection
    {
        return Booking::with(['service', 'specialist'])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['service', 'specialist'])
            ->latest()
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }
}
