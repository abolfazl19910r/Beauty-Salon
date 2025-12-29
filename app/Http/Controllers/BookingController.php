<?php

namespace App\Http\Controllers;

use App\Events\BookingCreated;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Specialist;
use App\Notifications\BookingNotification;
use App\Notifications\BookingRescheduledNotification;
use App\Notifications\CustomerBookingNotification;
use App\Services\PaymentService;
use App\Services\SMSService;
use App\Notifications\BookingStatusUpdated;
use App\Notifications\SpecialistBookingCancelledNotification;
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
use Illuminate\Support\Facades\Log;

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

            session([
                'pending_booking' => [
                    'service_id' => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'booking_time' => $bookingTime,
                ]
            ]);

            return view('bookings.confirm', compact('service', 'specialist', 'bookingTime', 'prepaymentAmount'));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            return back()->with('error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
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
                $service = BeautyService::find($request->service_id);
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

                $initialStatus = 'pending_payment';

                $booking = Booking::create([
                    'service_id' => $request->service_id,
                    'specialist_id' => $request->specialist_id,
                    'user_id' => auth()->id(),
                    'booking_time' => $bookingTime,
                    'status' => $initialStatus,
                    'prepayment_amount' => $finalAmount,
                    'payment_status' => 'unpaid',
                    'discount_code' => $request->discount_code,
                    'discount_amount' => $discountAmount
                ]);

                $booking->load(['service', 'specialist', 'user']);
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

    public function paymentCallback(Request $request, Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            $result = $this->paymentService->verify($request->all());

            if ($result['status'] === 'success') {
                DB::transaction(function() use ($booking, $result) {
                    $specialist = $booking->specialist;
                    $finalStatus = $specialist->hasAutoConfirm() ? 'confirmed' : 'pending';

                    $booking->update([
                        'payment_status' => 'paid',
                        'status' => $finalStatus,
                        'paid_at' => now(),
                        'payment_details' => $result
                    ]);

                    Log::info('✅ پرداخت موفق و Observer فعال شد', [
                        'booking_id' => $booking->id,
                        'status' => $finalStatus
                    ]);
                });

                session(['booking_id' => $booking->id]);

                return redirect()->route('bookings.success', ['id' => $booking->id])
                    ->with('success', 'رزرو با موفقیت انجام شد.');
            }

            $booking->update(['status' => 'cancelled']);

            return redirect()->route('bookings.failed')
                ->with('error', 'پرداخت ناموفق بود: ' . ($result['message'] ?? 'خطایی نامشخص'));

        } catch (Exception $e) {
            Log::error('❌ خطا در callback پرداخت', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            $booking->update(['status' => 'cancelled']);

            return redirect()->route('bookings.failed')
                ->with('error', 'خطا در تأیید پرداخت: ' . $e->getMessage());
        }
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'دسترسی غیرمجاز');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('info', 'این نوبت قبلاً لغو شده است.');
        }

        try {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'لغو توسط مشتری',
                'cancelled_by' => 'customer',
                'cancelled_at' => now()
            ]);
            return back()->with('success', '✓ نوبت شما با موفقیت لغو شد.');

        } catch (\Exception $e) {
            Log::error('خطا در لغو نوبت توسط مشتری', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'خطا در لغو نوبت: ' . $e->getMessage());
        }
    }

    public function index(Request $request): \Illuminate\View\View
    {
        $status = $request->query('status');
        $query = Booking::with(['service', 'specialist'])
            ->where('user_id', Auth::id())
            ->orderBy('booking_time', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function success(Request $request)
    {
        try {
            $booking = null;

            if ($request->has('id')) {
                $bookingId = $request->id;
                $booking = Booking::with(['service', 'specialist'])
                    ->where('id', $bookingId)
                    ->where('user_id', auth()->id())
                    ->first();
            } elseif (session()->has('booking_id')) {
                $bookingId = session('booking_id');
                $booking = Booking::with(['service', 'specialist'])
                    ->where('id', $bookingId)
                    ->where('user_id', auth()->id())
                    ->first();
            } else {
                $booking = Booking::with(['service', 'specialist'])
                    ->where('user_id', auth()->id())
                    ->where('payment_status', 'paid')
                    ->latest('paid_at')
                    ->first();
            }

            if (!$booking) {
                return redirect()->route('bookings.index')
                    ->with('error', 'اطلاعات رزرو یافت نشد. لطفاً با پشتیبانی تماس بگیرید.');
            }

            session(['last_paid_booking_id' => $booking->id]);

            return view('bookings.success', compact('booking'));

        } catch (Exception $e) {
            return redirect()->route('bookings.index')
                ->with('error', 'خطا در نمایش اطلاعات رزرو. لطفاً با پشتیبانی تماس بگیرید.');
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
                    'message' => 'کد تخفیف نامعتبر است یا منقضی شده.'
                ], 200);
            }

            if ($discountCode->user_id && $discountCode->user_id !== auth()->id()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'این کد تخفیف برای شما قابل استفاده نیست.'
                ], 200);
            }

            $prepaymentAmount = 50000;

            $discountAmount = $discountCode->type === 'percentage'
                ? ($prepaymentAmount * $discountCode->amount / 100)
                : $discountCode->amount;

            if ($discountCode->max_amount) {
                $discountAmount = min($discountAmount, $discountCode->max_amount);
            }

            $finalPrice = max(0, $prepaymentAmount - $discountAmount);

            return response()->json([
                'valid' => true,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'message' => 'کد تخفیف معتبر است!'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            Log::error('خطا در بررسی کد تخفیف', [
                'error' => $e->getMessage(),
                'code' => $request->code ?? null
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'خطا در بررسی کد تخفیف.'
            ], 500);
        }
    }

    public function getAvailableTimeSlots(Request $request, $specialist, $date): JsonResponse
    {
        try {
            if ($specialist instanceof Specialist) {
                $specialistModel = $specialist;
            } else {
                $specialistId = is_numeric($specialist) ? (int)$specialist : $specialist;
                $specialistModel = Specialist::findOrFail($specialistId);
            }

            $carbonDate = Carbon::parse($date);
            $dayOfWeek = $carbonDate->dayOfWeek;

            $serviceId = $request->query('service_id');
            $serviceDuration = null;

            if ($serviceId) {
                $service = BeautyService::find($serviceId);
                if ($service) {
                    $serviceDuration = $service->duration;
                }
            }

            if ($specialistModel->holidays()->whereDate('date', $date)->exists()) {
                return response()->json([
                    'slots' => [],
                    'message' => 'این روز تعطیل است'
                ]);
            }

            if ($specialistModel->leaves()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists()) {
                return response()->json([
                    'slots' => [],
                    'message' => 'متخصص در این روز مرخصی است'
                ]);
            }

            $schedule = $specialistModel->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'slots' => [],
                    'message' => 'این روز جزو روزهای کاری متخصص نیست'
                ]);
            }

            $availableSlots = $specialistModel->getAvailableSlots($date, $serviceDuration);

            if (empty($availableSlots)) {
                return response()->json([
                    'slots' => [],
                    'message' => 'هیچ زمان خالی برای این تاریخ وجود ندارد'
                ]);
            }

            return response()->json([
                'slots' => $availableSlots,
                'service_duration' => $serviceDuration,
                'schedule' => [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start ?? null,
                    'break_end' => $schedule->break_end ?? null
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد در getAvailableTimeSlots', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'slots' => [],
                'message' => 'متخصص مورد نظر یافت نشد'
            ], 404);
        } catch (Exception $e) {
            Log::error('خطا در دریافت اسلات‌های زمانی', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'date' => $date,
                'service_id' => $request->query('service_id'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'خطا در دریافت ساعت‌های در دسترس',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function processPayment(Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            if ($booking->payment_status === 'paid') {
                return redirect()->route('bookings.show', $booking)
                    ->with('info', 'این رزرو قبلاً پرداخت شده است.');
            }

            $result = $this->paymentService->pay([
                'amount' => $booking->prepayment_amount,
                'callback_url' => route('payment.callback', ['booking' => $booking->id]),
                'description' => 'پیش پرداخت نوبت سالن زیبایی - شناسه: ' . $booking->id
            ]);

            $booking->update([
                'payment_ref' => $result['ref_id']
            ]);

            return redirect($result['payment_url']);

        } catch (Exception $e) {
            return back()->with('error', 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage());
        }
    }

    public function applyDiscount(Request $request, Booking $booking): RedirectResponse
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'دسترسی غیرمجاز');
            }

            $request->validate([
                'code' => 'required|string'
            ]);

            $discountCode = DiscountCode::where('code', $request->code)->first();

            if (!$discountCode || !$discountCode->isValid()) {
                return back()->with('error', 'کد تخفیف نامعتبر است یا منقضی شده.');
            }

            if ($discountCode->user_id && $discountCode->user_id !== auth()->id()) {
                return back()->with('error', 'این کد تخفیف متعلق به شما نیست.');
            }

            DB::transaction(function() use ($booking, $discountCode) {
                $basePrepayment = 50000;
                $discountAmount = $discountCode->type === 'percentage'
                    ? ($basePrepayment * $discountCode->amount / 100)
                    : $discountCode->amount;
                if ($discountCode->max_amount) {
                    $discountAmount = min($discountAmount, $discountCode->max_amount);
                }
                $finalPrepayment = max(0, $basePrepayment - $discountAmount);
                $booking->update([
                    'discount_code' => $discountCode->code,
                    'discount_amount' => $discountAmount,
                    'prepayment_amount' => $finalPrepayment
                ]);
                $discountCode->increment('used_count');
            });

            return back()->with('success', sprintf(
                'کد تخفیف اعمال شد. مبلغ قابل پرداخت: %s تومان',
                number_format($booking->prepayment_amount)
            ));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('خطا در اعمال کد تخفیف', [
                'booking_id' => $booking->id,
                'code' => $request->code,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'خطا در اعمال کد تخفیف: ' . $e->getMessage());
        }
    }

    public function rate(Request $request, Booking $booking): RedirectResponse
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            $validated = $request->validate([
                'rating' => 'required|integer|between:1,5',
                'review' => 'nullable|string|max:500'
            ]);

            $booking->update($validated);

            $booking->specialist->notify(new \App\Notifications\NewReviewNotification($booking));

            auth()->user()->addLoyaltyPoints(10, 'ثبت نظر برای نوبت #' . $booking->id);

            return back()->with('success', 'نظر شما با موفقیت ثبت شد.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Exception $e) {
            return back()->with('error', 'خطا در ثبت نظر: ' . $e->getMessage());
        }
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

    public function getUserBookings(): Collection
    {
        return Booking::with(['service', 'specialist'])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->get();
    }

    /**
     *
     * @return JsonResponse
     */
    public function upcoming(): JsonResponse
    {
        $bookings = Booking::with(['service', 'specialist'])
            ->where('user_id', Auth::id())
            ->where('booking_time', '>', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('booking_time', 'asc')
            ->get();

        return response()->json([
            'bookings' => $bookings,
            'count' => $bookings->count()
        ]);
    }

    /**
     *
     * @return JsonResponse
     */
    public function past(): JsonResponse
    {
        $bookings = Booking::with(['service', 'specialist'])
            ->where('user_id', Auth::id())
            ->where('booking_time', '<=', now())
            ->orderBy('booking_time', 'desc')
            ->get();

        return response()->json([
            'bookings' => $bookings,
            'count' => $bookings->count()
        ]);
    }


    public function getAvailableDates($specialist): JsonResponse
    {
        try {
            if ($specialist instanceof Specialist) {
                $specialistModel = $specialist;
            } else {
                $specialistId = is_numeric($specialist) ? (int)$specialist : $specialist;
                $specialistModel = Specialist::findOrFail($specialistId);
            }

            $dates = [];
            $startDate = Carbon::today();

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                $schedule = $specialistModel->schedules()
                    ->where('day_of_week', $date->dayOfWeek)
                    ->where('is_active', true)
                    ->first();

                if (!$schedule) {
                    continue;
                }

                $hasLeave = $specialistModel->leaves()
                    ->where('start_date', '<=', $date->format('Y-m-d'))
                    ->where('end_date', '>=', $date->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->exists();

                $isHoliday = $specialistModel->holidays()
                    ->whereDate('date', $date)
                    ->exists();

                if (!$hasLeave && !$isHoliday) {
                    $dates[] = $date->format('Y-m-d');
                }
            }

            return response()->json($dates);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('متخصص یافت نشد', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'متخصص مورد نظر یافت نشد',
                'dates' => []
            ], 404);
        } catch (Exception $e) {
            Log::error('خطا در دریافت تاریخ‌ها', [
                'specialist_param' => is_object($specialist) ? get_class($specialist) : $specialist,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'خطا در دریافت تاریخ‌ها',
                'message' => $e->getMessage(),
                'dates' => []
            ], 500);
        }
    }

    public function failed(Request $request)
    {
        $bookingId = session('booking_id');
        $booking = null;

        if ($bookingId) {
            $booking = Booking::where('id', $bookingId)
                ->where('user_id', auth()->id())
                ->first();
        }

        $errorMessage = session('error') ?? 'متاسفانه پرداخت با خطا مواجه شد.';

        return view('bookings.failed', compact('booking', 'errorMessage'));
    }

    public function show(Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                abort(403, 'دسترسی غیرمجاز');
            }

            $booking->load(['service', 'specialist']);

            if (!$booking->service) {
                return redirect()->route('bookings.index')
                    ->with('error', 'اطلاعات سرویس برای این نوبت یافت نشد.');
            }

            if ($booking->payment_status === 'unpaid' &&
                $booking->status === 'pending_payment' &&
                !session()->has('from_payment_result')) {

                return redirect()->route('payment.show', ['booking' => $booking->id])
                    ->with('info', 'لطفاً ابتدا پرداخت را تکمیل کنید.');
            }

            session()->forget('from_payment_result');

            return view('bookings.show', compact('booking'));

        } catch (Exception $e) {
            Log::error('خطا در نمایش جزئیات نوبت', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('bookings.index')
                ->with('error', 'خطا در نمایش جزئیات نوبت: ' . $e->getMessage());
        }
    }

    public function reschedule(Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            if (!$booking->canBeRescheduled()) {
                return back()->with('error', 'امکان تغییر زمان این نوبت وجود ندارد.');
            }

            return view('bookings.reschedule', compact('booking'));

        } catch (Exception $e) {
            return back()->with('error', 'خطا در نمایش فرم تغییر زمان: ' . $e->getMessage());
        }
    }

    public function updateReschedule(Request $request, Booking $booking)
    {
        try {
            if ($booking->user_id !== auth()->id()) {
                throw new Exception('دسترسی غیرمجاز');
            }

            if (!$booking->canBeRescheduled()) {
                return back()->with('error', 'امکان تغییر زمان این نوبت وجود ندارد.');
            }

            $validated = $request->validate([
                'booking_time' => 'required|date|after:now',
            ]);

            $bookingTime = $request->booking_time;
            $specialist = $booking->specialist;
            $bookingDate = date('Y-m-d', strtotime($bookingTime));
            $bookingTimeOnly = date('H:i', strtotime($bookingTime));

            $availableSlots = $specialist->getAvailableSlots($bookingDate);

            if (!in_array($bookingTimeOnly, $availableSlots)) {
                return back()->with('error', 'زمان انتخاب شده در دسترس نیست.');
            }

            DB::transaction(function() use ($booking, $bookingTime) {
                $oldTime = $booking->booking_time;

                $booking->update([
                    'booking_time' => $bookingTime
                ]);

                $booking->specialist->notify(new BookingRescheduledNotification($booking, $oldTime));

                $message = sprintf(
                    'زمان نوبت شما با موفقیت از %s به %s تغییر یافت.',
                    verta($oldTime)->format('Y/m/d H:i'),
                    verta($bookingTime)->format('Y/m/d H:i')
                );
                $this->smsService->send($booking->user->phone, $message);
            });

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'زمان نوبت با موفقیت تغییر یافت.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            return back()->with('error', 'خطا در تغییر زمان نوبت: ' . $e->getMessage());
        }
    }

    /**
     *
     * @return JsonResponse
     */
    public function latestSuccessful(): JsonResponse
    {
        try {
            $booking = Booking::with(['service', 'specialist'])
                ->where('user_id', Auth::id())
                ->where('payment_status', 'paid')
                ->latest('paid_at')
                ->firstOrFail();

            return response()->json($booking);
        } catch (\Exception $e) {
            return response()->json(['error' => 'هیچ رزرو موفقی یافت نشد.'], 404);
        }
    }

    public function getSpecialistsByService($serviceId): JsonResponse
    {
        try {
            $service = BeautyService::findOrFail($serviceId);

            $specialists = $service->specialists()
                ->select('specialists.id', 'specialists.name', 'specialists.email', 'specialists.phone')
                ->get();

            return response()->json($specialists);
        } catch (Exception $e) {
            Log::error('خطا در دریافت متخصصین', [
                'service_id' => $serviceId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'خطا در دریافت متخصصین',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
