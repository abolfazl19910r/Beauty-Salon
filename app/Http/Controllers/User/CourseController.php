<?php

namespace App\Http\Controllers\User;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Controllers\Controller;
use App\Models\CoursePackage;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Services\Courses\CourseAvailability;
use App\Services\Courses\CourseException;
use App\Services\Courses\CoursePaymentService;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use App\Services\PaymentService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * دوره‌های درمانی در وب مشتری (بسته‌ی ب): فهرست و صفحه‌ی بسته‌ها (عمومی، فقط با امکان پلن)، خرید و پرداخت (کل یا پیش‌پرداخت،
 * کیف پول + درگاه)، «دوره‌های من»، رزرو جلسه‌ی بعد (بدون پیش‌پرداخت)، عکس‌های خود مشتری. دوره‌ی خریده‌شده حتی بعد از برداشته شدن
 * امکان از پلن سالن قابل استفاده است (مشتری پولش را داده).
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly CoursePaymentService $coursePayments,
        private readonly CourseAvailability $availability,
    ) {}

    // ─── عمومی ─────────────────────────────────────────────────────

    public function index(): View
    {
        $packages = CoursePackage::query()->onSale()->with(['service', 'specialist'])->get();

        return view('courses.index', compact('packages'));
    }

    public function show(CoursePackage $package): View
    {
        abort_unless($package->is_active && $package->is_public, 404);
        $package->load(['service', 'specialist']);

        return view('courses.show', compact('package'));
    }

    public function purchase(Request $request, CoursePackage $package): RedirectResponse
    {
        abort_unless($package->is_active && $package->is_public, 404);
        $input = $request->validate(['discount_code' => ['nullable', 'string', 'max:50']]);

        try {
            $course = $this->courses->purchase($request->user(), $package, $input['discount_code'] ?? null);
        } catch (CourseException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $course->status === 'active'
            ? redirect()->route('my-courses.show', $course)->with('success', 'دوره با تخفیف کامل برای شما فعال شد.')
            : redirect()->route('my-courses.pay', $course);
    }

    // ─── دوره‌های من ───────────────────────────────────────────────

    public function mine(Request $request): View
    {
        $courses = CustomerCourse::query()->forCustomer($request->user()->id)
            ->where(fn ($q) => $q->where('status', '!=', 'cancelled')->orWhere('amount_paid', '>', 0))
            ->latest('id')->get();

        return view('courses.mine', compact('courses'));
    }

    public function mineShow(Request $request, CustomerCourse $course, CourseSessionRecords $records): View
    {
        $this->authorizeOwn($request, $course);

        $bookings = $course->bookings()->with(['specialist'])->orderBy('booking_time')->get();
        ['notes' => $notes, 'photos' => $photos] = $records->recordsFor($course, customerView: true);
        $reserved = $course->reservedCount();

        return view('courses.mine-show', [
            'course' => $course,
            'bookings' => $bookings,
            'notes' => $notes,
            'photos' => $photos,
            'blocker' => $course->bookingBlocker($reserved),
            'remaining' => $course->remainingSessions($reserved),
            'payments' => $course->payments()->latest('id')->get(),
        ]);
    }

    public function payForm(Request $request, CustomerCourse $course, PaymentService $paymentService): View|RedirectResponse
    {
        $this->authorizeOwn($request, $course);
        if ($course->balanceDue() <= 0 || in_array($course->status, ['cancelled', 'completed', 'expired'], true)) {
            return redirect()->route('my-courses.show', $course);
        }

        $first = $course->status === 'pending_payment';

        return view('courses.pay', [
            'course' => $course,
            'minAmount' => $first ? $course->min_prepayment : min(CoursePaymentService::MIN_PARTIAL, $course->balanceDue()),
            'walletBalance' => (float) ($request->user()->wallet()->first()?->balance ?? 0),
            'gateways' => $paymentService->gatewayOptions($course->balanceDue()),
            'onlinePayment' => app(CurrentSalon::class)->get()->acceptsOnlinePayments(),
        ]);
    }

    public function pay(Request $request, CustomerCourse $course): RedirectResponse|Response
    {
        $this->authorizeOwn($request, $course);
        $input = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'use_wallet' => ['nullable', 'boolean'],
            'gateway_id' => ['nullable', 'integer'],
        ]);

        $wallet = $request->boolean('use_wallet') ? (float) ($request->user()->wallet()->first()?->balance ?? 0) : 0.0;
        $result = $this->coursePayments->start(
            $request->user(), $course, (float) $input['amount'], $wallet,
            isset($input['gateway_id']) ? (int) $input['gateway_id'] : null,
            route('my-courses.payment.callback'),
        );

        return match ($result['state']) {
            'paid' => redirect()->route('my-courses.show', $course)->with('success', $result['message']),
            'redirect' => \App\Payments\GatewayRedirect::to($result['gateway']),
            default => back()->withInput()->with('error', $result['message']),
        };
    }

    public function callback(Request $request): RedirectResponse
    {
        $outcome = $this->coursePayments->finalize($request);
        $course = $outcome['course'];

        if (! $course || (int) $course->user_id !== (int) $request->user()->id) {
            return redirect()->route('my-courses.index')->with('error', $outcome['message']);
        }

        return redirect()->route('my-courses.show', $course)->with($outcome['state'] === 'paid' ? 'success' : 'error', $outcome['message']);
    }

    // ─── رزرو جلسه ─────────────────────────────────────────────────

    public function bookForm(Request $request, CustomerCourse $course): View|RedirectResponse
    {
        $this->authorizeOwn($request, $course);
        if ($blocker = $course->bookingBlocker()) {
            return redirect()->route('my-courses.show', $course)->with('error', $blocker);
        }

        $specialists = $this->availability->specialists($course);
        $specialist = $specialists->firstWhere('id', (int) $request->query('specialist')) ?? ($specialists->count() === 1 ? $specialists->first() : null);
        $dates = $specialist ? $this->availability->dates($course, $specialist) : [];
        $date = in_array($request->query('date'), $dates, true) ? $request->query('date') : null;
        $slots = $specialist && $date ? $this->availability->slots($course, $specialist, $date) : [];

        return view('courses.book', compact('course', 'specialists', 'specialist', 'dates', 'date', 'slots'));
    }

    public function book(Request $request, CustomerCourse $course): RedirectResponse
    {
        $this->authorizeOwn($request, $course);
        $input = $request->validate([
            'specialist_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        try {
            $booking = $this->courses->bookSession($course, (int) $input['specialist_id'], $input['date'].' '.$input['time'].':00');
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        } catch (BookingNotAvailableException) {
            return back()->with('error', 'این ساعت همین الان رزرو شد؛ ساعت دیگری انتخاب کنید.');
        }

        return redirect()->route('my-courses.show', $course)
            ->with('success', sprintf('%s رزرو شد: %s ساعت %s', $course->sessionLabel((int) $booking->course_session_number),
                verta($booking->booking_time)->format('Y/m/d'), $booking->booking_time->format('H:i')));
    }

    // ─── عکس‌ها (تصمیم ۶: خود مشتری می‌بیند و می‌تواند حذف کند) ─────────

    public function photo(Request $request, CustomerCourse $course, CourseSessionPhoto $photo, CourseSessionRecords $records)
    {
        $this->authorizeOwn($request, $course);
        abort_unless((int) $photo->customer_course_id === (int) $course->id, 404);

        return $records->stream($photo);
    }

    public function destroyPhoto(Request $request, CustomerCourse $course, CourseSessionPhoto $photo): RedirectResponse
    {
        $this->authorizeOwn($request, $course);
        abort_unless((int) $photo->customer_course_id === (int) $course->id, 404);

        $photo->delete();

        return back()->with('success', 'عکس حذف شد.');
    }

    /** بسته‌ی مجوز 5د: مشتری رضایت عکس را پس می‌گیرد (ماده‌ی ۵۹) */
    public function withdrawConsent(Request $request, CustomerCourse $course, \App\Services\Courses\CourseService $courses): RedirectResponse
    {
        $this->authorizeOwn($request, $course);
        $courses->withdrawConsent($course, $request->user(), 'web', $request);

        return back()->with('success', 'رضایت عکس پس گرفته شد؛ از این به بعد عکسی گرفته نمی‌شود. عکس‌های قبلی را می‌توانید خودتان پاک کنید.');
    }

    private function authorizeOwn(Request $request, CustomerCourse $course): void
    {
        abort_unless((int) $course->user_id === (int) $request->user()->id, 404);
    }
}
