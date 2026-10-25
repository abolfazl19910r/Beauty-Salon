<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Exceptions\BookingNotAvailableException;
use App\Http\Responses\ApiResponse;
use App\Models\CoursePackage;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\Specialist;
use App\Services\Api\CoursePresenter;
use App\Services\Api\CustomerPresenter;
use App\Services\Courses\CourseAvailability;
use App\Services\Courses\CoursePaymentService;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use App\Services\Payment\AppCheckout;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * دوره‌های درمانی در اپ «ماهرو» (بسته‌ی ب). همان قاعده‌های وب (CourseService / CoursePaymentService / CourseAvailability).
 * بسته‌ها و خرید فقط با امکان treatment_courses (route: feature:...,hide)؛ «دوره‌های من»، پرداخت باقی‌مانده و رزرو جلسه همیشه
 * — دوره‌ی خریده‌شده بعد از تنزل پلن هم قابل استفاده است. پرداخت: مثل نوبت، checkout_url در مرورگر بیرونی و وضعیت قطعی از
 * GET payments/{tx}. خطای قاعده‌ی دوره: CourseException ← ‏422 با code «course».
 */
class CustomerCourseController extends CustomerController
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly CoursePaymentService $coursePayments,
        private readonly CourseAvailability $availability,
    ) {}

    /** GET courses/packages */
    public function packages(): JsonResponse
    {
        $packages = CoursePackage::query()->onSale()->with(['service', 'specialist'])->get();

        return ApiResponse::success($packages->map(fn (CoursePackage $p) => CoursePresenter::package($p))->values());
    }

    /** POST courses/packages/{packageId}/purchase {discount_code?} */
    public function purchase(Request $request, int $packageId): JsonResponse
    {
        $input = $request->validate(['discount_code' => ['nullable', 'string', 'max:50']]);
        $package = CoursePackage::query()->onSale()->find($packageId);
        if (! $package) {
            throw new ApiException('not_found', 'بسته‌ی دوره پیدا نشد.', 404);
        }

        $course = $this->courses->purchase($this->customer($request), $package, $input['discount_code'] ?? null);

        return ApiResponse::success(CoursePresenter::course($course->fresh()), [], 201);
    }

    /** GET courses */
    public function index(Request $request): JsonResponse
    {
        $courses = CustomerCourse::query()->forCustomer($this->customer($request)->id)
            ->where(fn ($q) => $q->where('status', '!=', 'cancelled')->orWhere('amount_paid', '>', 0))
            ->latest('id')->get();

        return ApiResponse::success($courses->map(fn (CustomerCourse $c) => CoursePresenter::course($c))->values());
    }

    /** GET courses/{courseId} */
    public function show(Request $request, int $courseId, CourseSessionRecords $records): JsonResponse
    {
        $course = $this->own($request, $courseId);
        $bookings = $course->bookings()->with('specialist')->orderBy('booking_time')->get();

        return ApiResponse::success(CoursePresenter::detail(
            $course, $bookings, $records->recordsFor($course, customerView: true), 'api.v1.customer.courses.photos.show',
        ));
    }

    /** GET courses/{courseId}/specialists */
    public function specialists(Request $request, int $courseId): JsonResponse
    {
        $course = $this->own($request, $courseId);

        return ApiResponse::success($this->availability->specialists($course)->map(fn (Specialist $s) => [
            'id' => $s->id, 'name' => $s->name, 'photo_url' => $s->photoUrl(),
        ])->values());
    }

    /** GET courses/{courseId}/dates?specialist_id= */
    public function dates(Request $request, int $courseId): JsonResponse
    {
        [$course, $specialist] = $this->courseAndSpecialist($request, $courseId);
        [$from, $to] = $this->availability->window($course);

        return ApiResponse::success([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'dates' => $this->availability->dates($course, $specialist),
        ]);
    }

    /** GET courses/{courseId}/slots?specialist_id=&date=Y-m-d */
    public function slots(Request $request, int $courseId): JsonResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        [$course, $specialist] = $this->courseAndSpecialist($request, $courseId);

        return ApiResponse::success([
            'date' => $request->query('date'),
            'slots' => in_array($request->query('date'), $this->availability->dates($course, $specialist), true)
                ? $this->availability->slots($course, $specialist, (string) $request->query('date'))
                : [],
        ]);
    }

    /** POST courses/{courseId}/bookings {specialist_id, date, time} — جلسه‌ی بعد، بدون پیش‌پرداخت */
    public function book(Request $request, int $courseId): JsonResponse
    {
        $course = $this->own($request, $courseId);
        $input = $request->validate([
            'specialist_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        try {
            $booking = $this->courses->bookSession($course, (int) $input['specialist_id'], $input['date'].' '.$input['time'].':00', false, 'online');
        } catch (BookingNotAvailableException) {
            throw new ApiException('slot_taken', 'این ساعت همین الان رزرو شد؛ ساعت دیگری انتخاب کنید.', 409);
        }

        return ApiResponse::success([
            'booking' => CustomerPresenter::booking($booking->load(['service', 'specialist'])),
            'course' => CoursePresenter::course($course->fresh()),
        ], [], 201);
    }

    /** GET courses/{courseId}/pay — مبلغ‌ها و درگاه‌ها برای صفحه‌ی پرداخت */
    public function payOptions(Request $request, int $courseId, PaymentService $paymentService): JsonResponse
    {
        $course = $this->own($request, $courseId);
        $first = $course->status === 'pending_payment';

        return ApiResponse::success([
            'balance_due' => $course->balanceDue(),
            'min_amount' => $first ? $course->min_prepayment : min(CoursePaymentService::MIN_PARTIAL, $course->balanceDue()),
            'wallet_balance' => (float) ($this->customer($request)->wallet()->first()?->balance ?? 0),
            'online_payment' => $this->salon()->acceptsOnlinePayments(),
            'gateways' => $paymentService->gatewayOptions($course->balanceDue()),
        ]);
    }

    /** POST courses/{courseId}/pay {amount, use_wallet?, gateway_id?} */
    public function pay(Request $request, int $courseId, AppCheckout $checkout): JsonResponse
    {
        $course = $this->own($request, $courseId);
        $input = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'use_wallet' => ['nullable', 'boolean'],
            'gateway_id' => ['nullable', 'integer'],
        ]);

        $user = $this->customer($request);
        $wallet = $request->boolean('use_wallet') ? (float) ($user->wallet()->first()?->balance ?? 0) : 0.0;
        $result = $this->coursePayments->start(
            $user, $course, (float) $input['amount'], $wallet,
            isset($input['gateway_id']) ? (int) $input['gateway_id'] : null,
            route('app.course.callback', ['salon_slug' => $this->salon()->slug]),
        );

        return match ($result['state']) {
            'paid' => ApiResponse::success(['state' => 'paid', 'course' => CoursePresenter::course($result['course'])]),
            'redirect' => ApiResponse::success([
                'state' => 'redirect',
                'checkout_url' => $checkout->remember($this->salon(), $result['gateway']),
                'transaction' => $result['gateway']['transaction'],
                'gateway' => $result['gateway']['gateway'] ?? null,
                'fee' => (int) ($result['gateway']['fee'] ?? 0),
                'expires_in' => (int) config('api.checkout_ttl_minutes', 15) * 60,
            ]),
            default => throw new ApiException('payment_unavailable', $result['message'], 422),
        };
    }

    /** GET course-photos/{photoId} — فقط عکس دوره‌ی خود مشتری */
    public function photo(Request $request, int $photoId, CourseSessionRecords $records)
    {
        return $records->stream($this->ownPhoto($request, $photoId));
    }

    /** DELETE course-photos/{photoId} — تصمیم ۶: مشتری عکس خودش را حذف می‌کند */
    /** بسته‌ی مجوز 5د: مشتری رضایت عکس را پس می‌گیرد؛ از این به بعد عکسی آپلود نمی‌شود */
    public function withdrawConsent(Request $request, int $courseId, \App\Services\Courses\CourseService $courses): JsonResponse
    {
        $course = $this->own($request, $courseId);
        $courses->withdrawConsent($course, $this->customer($request));

        return ApiResponse::success(['photo_consent' => false]);
    }

    public function destroyPhoto(Request $request, int $photoId): JsonResponse
    {
        $this->ownPhoto($request, $photoId)->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    private function own(Request $request, int $courseId): CustomerCourse
    {
        $course = CustomerCourse::query()->forCustomer($this->customer($request)->id)->find($courseId);
        if (! $course) {
            throw new ApiException('not_found', 'دوره پیدا نشد.', 404);
        }

        return $course;
    }

    private function ownPhoto(Request $request, int $photoId): CourseSessionPhoto
    {
        $photo = CourseSessionPhoto::query()->find($photoId);
        if (! $photo || ! CustomerCourse::query()->forCustomer($this->customer($request)->id)->whereKey($photo->customer_course_id)->exists()) {
            throw new ApiException('not_found', 'عکس پیدا نشد.', 404);
        }

        return $photo;
    }

    /** @return array{0: CustomerCourse, 1: Specialist} */
    private function courseAndSpecialist(Request $request, int $courseId): array
    {
        $course = $this->own($request, $courseId);
        $request->validate(['specialist_id' => ['required', 'integer']]);
        $specialist = $this->availability->specialists($course)->firstWhere('id', (int) $request->query('specialist_id'));
        if (! $specialist) {
            throw new ApiException('not_found', 'این متخصص این دوره را انجام نمی‌دهد.', 404);
        }

        return [$course, $specialist];
    }
}
