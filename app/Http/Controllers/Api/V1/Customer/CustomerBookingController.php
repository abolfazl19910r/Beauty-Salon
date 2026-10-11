<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Services\Api\CustomerPresenter;
use App\Services\Booking\BookingRatingService;
use App\Services\Booking\BookingRescheduleService;
use App\Services\Booking\BookingService;
use App\Services\PaymentService;
use App\Support\Idempotency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * نوبت‌های مشتری در اپ «ماهرو» (بسته‌ی ۳): فهرست، جزئیات، پیش‌فاکتور، رزرو، لغو، جابه‌جایی، امتیاز.
 * قاعده‌ها همان وب (BookingService، BookingPolicy، BookingRescheduleService، BookingRatingService). نوبت مشتری دیگر ۴۰۴.
 * پرداخت: CustomerPaymentController.
 */
class CustomerBookingController extends CustomerController
{
    public const ACTIVE_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    public function __construct(
        protected readonly BookingService $bookingService,
        protected readonly PaymentService $paymentService,
    ) {}

    /** GET bookings?scope=upcoming|past (پیش‌فرض upcoming) */
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate([
            'scope' => ['nullable', 'in:upcoming,past'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $upcoming = ($input['scope'] ?? 'upcoming') === 'upcoming';

        $query = Booking::query()->where('user_id', $this->customer($request)->id)->with(['service', 'specialist']);

        $upcoming
            ? $query->whereIn('status', self::ACTIVE_STATUSES)->where('booking_time', '>=', now())->orderBy('booking_time')
            : $query->where(fn ($q) => $q->whereNotIn('status', self::ACTIVE_STATUSES)->orWhere('booking_time', '<', now()))
                ->orderByDesc('booking_time');

        $page = $query->paginate((int) ($input['per_page'] ?? 20));

        return ApiResponse::success(
            collect($page->items())->map(fn (Booking $b) => CustomerPresenter::booking($b))->values(),
            $this->pageMeta($page) + ['scope' => $upcoming ? 'upcoming' : 'past'],
        );
    }

    public function show(Request $request, int $bookingId): JsonResponse
    {
        return ApiResponse::success(CustomerPresenter::booking($this->ownBooking($request, $bookingId)));
    }

    /**
     * POST bookings/quote {service_id, discount_code?} — پیش‌پرداخت، تخفیف (از باقی‌مانده کم می‌شود، نه از
     * پیش‌پرداخت)، باقی‌مانده و درگاه‌ها با کارمزد، پیش از رزرو.
     */
    public function quote(Request $request): JsonResponse
    {
        $input = $request->validate([
            'service_id' => ['required', 'integer'],
            'discount_code' => ['nullable', 'string', 'max:50'],
        ]);

        $service = BeautyService::query()->findOrFail((int) $input['service_id']);
        $discount = $this->checkedDiscount($request, $input['discount_code'] ?? null, (float) $service->price);

        return ApiResponse::success($this->quoteFor($request, $service, $discount));
    }

    /** POST bookings {service_id, specialist_id, booking_time, discount_code?, idempotency_key?} */
    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'service_id' => ['required', 'integer'],
            'specialist_id' => ['required', 'integer'],
            'booking_time' => ['required', 'date', 'after:now'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'idempotency_key' => Idempotency::rule(),
        ]);

        $service = BeautyService::query()->findOrFail((int) $input['service_id']);
        $specialist = Specialist::query()->findOrFail((int) $input['specialist_id']);
        if (! $specialist->services()->whereKey($service->id)->exists()) {
            throw new ApiException('service_not_offered', 'این متخصص این خدمت را انجام نمی‌دهد.', 422);
        }

        $discount = $this->checkedDiscount($request, $input['discount_code'] ?? null, (float) $service->price);
        $prepayment = $this->bookingService->calculatePrepayment((float) $service->price)['original_amount'];

        // مثل وب: بدون مرچنت/درگاه، نوبتی که پیش‌پرداخت لازم دارد ثبت نمی‌شود (پول مشتری هرگز به حساب پلتفرم نمی‌رود)
        if ($prepayment > 0 && ! $this->salon()->acceptsOnlinePayments()) {
            throw new ApiException('online_payment_unavailable', \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE, 422);
        }

        $booking = $this->bookingService->createBooking(
            userId: $this->customer($request)->id,
            serviceId: $service->id,
            specialistId: $specialist->id,
            bookingTime: Carbon::parse($input['booking_time'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            discountCode: $discount ? $input['discount_code'] : null,
            idempotencyKey: $input['idempotency_key'] ?? null,
        );

        $booking->load(['service', 'specialist']);

        return ApiResponse::success(
            CustomerPresenter::booking($booking),
            ['payment' => $this->paymentInfo($request, $booking)],
            $booking->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function cancel(Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->ownBooking($request, $bookingId);

        if ($booking->status === 'cancelled') {
            return ApiResponse::success(CustomerPresenter::booking($booking), ['changed' => false]);
        }
        if (! $request->user()->can('cancel', $booking)) {
            throw new ApiException('booking_not_cancellable', 'لغو نوبت فقط تا '.CustomerPresenter::CANCEL_DEADLINE_HOURS.' ساعت پیش از زمان آن ممکن است. برای لغو با سالن تماس بگیرید.', 409);
        }

        $this->bookingService->cancelBooking($booking);

        return ApiResponse::success(CustomerPresenter::booking($booking->fresh(['service', 'specialist'])), [
            'changed' => true,
            'wallet_balance' => (float) ($request->user()->wallet()->first()?->balance ?? 0),
        ]);
    }

    /** POST bookings/{id}/reschedule {booking_time} — همان متخصص و خدمت (مثل وب). */
    public function reschedule(Request $request, int $bookingId, BookingRescheduleService $rescheduler): JsonResponse
    {
        $input = $request->validate(['booking_time' => ['required', 'date', 'after:now']]);
        $booking = $this->ownBooking($request, $bookingId);

        if (! $request->user()->can('reschedule', $booking)) {
            throw new ApiException('booking_not_reschedulable', 'تغییر زمان فقط برای نوبت تأییدشده یا در انتظار تأیید و تا ۲۴ ساعت پیش از آن ممکن است.', 409);
        }

        $rescheduler->reschedule($booking, Carbon::parse($input['booking_time'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'));

        return ApiResponse::success(CustomerPresenter::booking($booking->fresh(['service', 'specialist'])));
    }

    public function rate(Request $request, int $bookingId, BookingRatingService $rating): JsonResponse
    {
        $input = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $rating->rate($request->user(), $this->ownBooking($request, $bookingId), (int) $input['rating'], $input['review'] ?? null);

        return ApiResponse::success(CustomerPresenter::booking($booking->fresh(['service', 'specialist'])));
    }

    protected function ownBooking(Request $request, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->whereKey($bookingId)
            ->where('user_id', $this->customer($request)->id)
            ->with(['service', 'specialist'])
            ->first();

        if (! $booking) {
            throw new ApiException('not_found', 'نوبت پیدا نشد.', 404);
        }

        return $booking;
    }

    /** کد تخفیف معتبرِ متعلق به همین مشتری (کد شخصی مشتری دیگر رد می‌شود)؛ بدون کد → null */
    private function checkedDiscount(Request $request, ?string $code, float $price): ?array
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $this->bookingService->validateDiscountCode($code, $this->customer($request)->id, $price);

        return $this->bookingService->calculatePrepayment($price, $code);
    }

    private function quoteFor(Request $request, BeautyService $service, ?array $discount): array
    {
        $calc = $discount ?? $this->bookingService->calculatePrepayment((float) $service->price);
        $prepayment = (float) $calc['original_amount'];

        return [
            'service' => CustomerPresenter::service($service),
            'price' => (float) $service->price,
            'prepayment_amount' => $prepayment,
            'discount_code' => $calc['discount_code'],
            'discount_amount' => (float) $calc['discount_amount'],
            'remaining_amount' => max(0.0, (float) $service->price - $prepayment - (float) $calc['discount_amount']),
            'online_payment' => $this->salon()->acceptsOnlinePayments(),
            'gateways' => $this->paymentService->gatewayOptions($prepayment),
            'wallet_balance' => (float) ($this->customer($request)->wallet?->balance ?? 0),
        ];
    }

    private function paymentInfo(Request $request, Booking $booking): array
    {
        return [
            'required' => $booking->isPayable() && (float) $booking->prepayment_amount > 0,
            'amount' => (float) $booking->prepayment_amount,
            'gateways' => $this->paymentService->gatewayOptions((float) $booking->prepayment_amount),
            'wallet_balance' => (float) ($this->customer($request)->wallet()->first()?->balance ?? 0),
        ];
    }
}
