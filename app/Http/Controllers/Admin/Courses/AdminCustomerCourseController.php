<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CoursePackage;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Courses\CourseException;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use App\Support\CurrentSalon;
use App\Support\JalaliDateInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * دوره‌های فروخته‌شده در پنل مدیر (بسته‌ی ب): فهرست و فیلتر، صفحه‌ی هر دوره (پیشرفت، جلسه‌ها با یادداشت و عکس، پرداخت‌ها)، رزرو
 * جلسه، ثبت پرداخت حضوری، تمدید مهلت، لغو و برگشت به کیف پول، رضایت عکس، و فروش حضوری. فروش تازه فقط با امکان پلن؛ بقیه حتی بعد
 * از تنزل پلن برای دوره‌های موجود کار می‌کنند (تعهد به مشتری).
 */
class AdminCustomerCourseController extends Controller
{
    public const FILTERS = ['overdue' => 'موعد گذشته، بدون نوبت', 'expiring' => 'نزدیک انقضا (۳۰ روز)'];

    public function __construct(
        private readonly CourseService $courses,
        private readonly CourseSessionRecords $records,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_keys(CustomerCourse::STATUSES))],
            'service_id' => ['nullable', 'integer'],
            'specialist_id' => ['nullable', 'integer'],
            'filter' => ['nullable', 'in:'.implode(',', array_keys(self::FILTERS))],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $courses = CustomerCourse::query()->with(['user', 'service'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['service_id'] ?? null, fn ($q, $v) => $q->where('service_id', $v))
            ->when($filters['specialist_id'] ?? null, fn ($q, $v) => $q->whereHas('bookings', fn ($b) => $b->where('specialist_id', $v)))
            ->when(($filters['filter'] ?? null) === 'overdue', fn ($q) => $q->where('status', 'active')->where('next_due_at', '<=', now())
                ->whereDoesntHave('bookings', fn ($b) => $b->whereIn('status', CustomerCourse::ACTIVE_BOOKING_STATUSES)))
            ->when(($filters['filter'] ?? null) === 'expiring', fn ($q) => $q->where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(30)]))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('phone', 'like', '%'.$v.'%')->orWhere('name', 'like', '%'.$v.'%')))
            ->latest('id')->paginate(25)->withQueryString();

        $salon = app(CurrentSalon::class)->get();

        return view('admin.courses.index', [
            'courses' => $courses,
            'filters' => $filters,
            'services' => \App\Models\BeautyService::query()->whereIn('id', CustomerCourse::query()->select('service_id'))->orderBy('name')->get(),
            'specialists' => Specialist::query()->orderBy('name')->get(),
            'canSell' => $salon?->hasFeature(\App\Support\Features\Feature::TreatmentCourses) ?? true,
            'stats' => [
                'active' => CustomerCourse::query()->where('status', 'active')->count(),
                'overdue' => CustomerCourse::query()->where('status', 'active')->where('next_due_at', '<=', now())
                    ->whereDoesntHave('bookings', fn ($b) => $b->whereIn('status', CustomerCourse::ACTIVE_BOOKING_STATUSES))->count(),
                'liability' => app(\App\Services\Courses\CourseReports::class)->liability(),
            ],
        ]);
    }

    public function show(CustomerCourse $course): View
    {
        $course->load(['user', 'service', 'specialist', 'payments.recordedBy', 'soldBy']);
        $bookings = $course->bookings()->with('specialist')->orderBy('booking_time')->get();
        ['notes' => $notes, 'photos' => $photos] = $this->records->recordsFor($course);

        return view('admin.courses.show', [
            'course' => $course,
            'bookings' => $bookings,
            'notes' => $notes,
            'photos' => $photos,
            'blocker' => $course->bookingBlocker(),
            'refundable' => $this->courses->refundableAmount($course),
            'specialists' => app(\App\Services\Courses\CourseAvailability::class)->specialists($course),
            'activity' => \Spatie\Activitylog\Models\Activity::query()->with('causer')->where('subject_type', $course->getMorphClass())->where('subject_id', $course->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function sellForm(): View
    {
        return view('admin.courses.sell', ['packages' => CoursePackage::query()->where('is_active', true)->with('service')->orderBy('sort')->get()]);
    }

    public function sell(Request $request): RedirectResponse
    {
        $request->merge(['amount' => $this->digits($request->input('amount')), 'customer_phone' => $this->digits($request->input('customer_phone'))]);
        $input = $request->validate([
            'package_id' => ['required', 'integer'],
            'customer_phone' => ['required', 'regex:/^09\d{9}$/'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['customer_phone.regex' => 'شماره‌ی موبایل را به شکل ۰۹۱۲۱۲۳۴۵۶۷ وارد کنید.'], ['amount' => 'مبلغ دریافتی']);

        $package = CoursePackage::query()->where('is_active', true)->findOrFail($input['package_id']);
        $salonId = (int) app(CurrentSalon::class)->id();
        $customer = User::query()->where('phone', $input['customer_phone'])->where('salon_id', $salonId)->where('user_type', 'customer')->first();

        if (! $customer) {
            if (blank($input['customer_name'] ?? null)) {
                return back()->withInput()->with('error', 'مشتری‌ای با این شماره در سالن نیست؛ نامش را هم وارد کنید تا ساخته شود.');
            }
            $customer = User::create([
                'name' => $input['customer_name'], 'phone' => $input['customer_phone'], 'password' => Hash::make(Str::random(40)),
                'salon_id' => $salonId, 'user_type' => 'customer',
            ]);
        }

        try {
            $course = $this->courses->purchase($customer, $package, $input['discount_code'] ?? null, $request->user());
            if ($error = $this->inPersonAmountError($course, (float) $input['amount'])) {
                $this->courses->abandon($course, 'فروش حضوری نیمه‌کاره');

                return back()->withInput()->with('error', $error);
            }
            if ((float) $input['amount'] > 0) {
                $this->courses->recordPayment($course, (float) $input['amount'], 'in_person', ['recorded_by' => $request->user()->id, 'note' => $input['note'] ?? null]);
            }
        } catch (CourseException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        activity()->performedOn($course)->causedBy($request->user())->withProperties(['amount' => (float) $input['amount']])->log('course_sold_in_person');

        return redirect()->route('admin.courses.show', $course)->with('success', 'دوره برای مشتری ثبت شد.');
    }

    public function recordPayment(Request $request, CustomerCourse $course): RedirectResponse
    {
        $request->merge(['amount' => $this->digits($request->input('amount'))]);
        $input = $request->validate(['amount' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string', 'max:255']], [], ['amount' => 'مبلغ']);

        if ($error = $this->inPersonAmountError($course, (float) $input['amount'])) {
            return back()->with('error', $error);
        }

        try {
            $this->courses->recordPayment($course, (float) $input['amount'], 'in_person', ['recorded_by' => $request->user()->id, 'note' => $input['note'] ?? null]);
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }
        activity()->performedOn($course)->causedBy($request->user())->withProperties(['amount' => (float) $input['amount']])->log('course_payment_in_person');

        return back()->with('success', 'پرداخت حضوری ثبت شد.');
    }

    public function book(Request $request, CustomerCourse $course): RedirectResponse
    {
        $input = $request->validate([
            'specialist_id' => ['required', 'integer'],
            'date' => ['required', 'string', 'max:20'],
            'time' => ['required', 'date_format:H:i'],
        ], [], ['date' => 'تاریخ', 'time' => 'ساعت']);

        $date = JalaliDateInput::toCarbon($input['date']);
        if (! $date) {
            return back()->withInput()->with('error', 'تاریخ را به شکل ۱۴۰۵/۰۸/۱۰ وارد کنید.');
        }

        try {
            $booking = $this->courses->bookSession($course, (int) $input['specialist_id'], $date->format('Y-m-d').' '.$input['time'].':00', byStaff: true, source: 'phone');
        } catch (CourseException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (BookingNotAvailableException) {
            return back()->withInput()->with('error', 'این ساعت برای این متخصص آزاد نیست.');
        }

        $warning = $booking->booking_time->lt($course->earliestNextSession($booking->id)) ? ' (زودتر از فاصله‌ی حداقل دوره)' : '';

        return back()->with('success', $course->sessionLabel((int) $booking->course_session_number).' رزرو شد.'.$warning);
    }

    public function extend(Request $request, CustomerCourse $course): RedirectResponse
    {
        $date = JalaliDateInput::toCarbon((string) $request->input('expires_at'), endOfDay: true);
        if (! $date || $date->isPast()) {
            return back()->with('error', 'تاریخ تازه‌ی مهلت باید یک تاریخ شمسی آینده باشد.');
        }

        try {
            $this->courses->extend($course, $date, $request->user());
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'مهلت دوره تمدید شد.');
    }

    public function cancel(Request $request, CustomerCourse $course): RedirectResponse
    {
        $request->merge(['refund' => $this->digits($request->input('refund'))]);
        $input = $request->validate(['refund' => ['required', 'integer', 'min:0'], 'reason' => ['required', 'string', 'max:255']], [], ['refund' => 'مبلغ برگشت', 'reason' => 'دلیل']);

        try {
            $this->courses->cancel($course, (float) $input['refund'], $input['reason'], $request->user());
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'دوره لغو شد'.((float) $input['refund'] > 0 ? ' و مبلغ به کیف پول مشتری برگشت.' : '.'));
    }

    public function consent(Request $request, CustomerCourse $course): RedirectResponse
    {
        $this->courses->recordConsent($course, $request->user(), $request);

        return back()->with('success', 'رضایت مشتری برای عکس قبل/بعد ثبت شد.');
    }

    // ─── یادداشت و عکس هر جلسه ──────────────────────────────────────

    public function saveNote(Request $request, CustomerCourse $course, Booking $booking): RedirectResponse
    {
        abort_unless((int) $booking->customer_course_id === (int) $course->id, 404);
        $input = $request->validate(['note' => ['nullable', 'string', 'max:'.\App\Models\CourseSessionNote::MAX_LENGTH]]);
        $this->records->saveNote($booking, null, $input['note'] ?? null, $request->boolean('visible_to_customer'));

        return back()->with('success', 'یادداشت جلسه ذخیره شد.');
    }

    public function uploadPhoto(Request $request, CustomerCourse $course, Booking $booking): RedirectResponse
    {
        abort_unless((int) $booking->customer_course_id === (int) $course->id, 404);
        $request->validate(['photo' => CourseSessionRecords::PHOTO_RULES, 'kind' => ['required', 'in:before,after']]);

        try {
            $this->records->addPhoto($booking, $request->file('photo'), (string) $request->input('kind'), $request->user());
        } catch (CourseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'عکس ذخیره شد.');
    }

    public function photo(CustomerCourse $course, CourseSessionPhoto $photo)
    {
        abort_unless((int) $photo->customer_course_id === (int) $course->id, 404);

        return $this->records->stream($photo);
    }

    public function destroyPhoto(CustomerCourse $course, CourseSessionPhoto $photo): RedirectResponse
    {
        abort_unless((int) $photo->customer_course_id === (int) $course->id, 404);
        $photo->delete();

        return back()->with('success', 'عکس حذف شد.');
    }

    private function inPersonAmountError(CustomerCourse $course, float $amount): ?string
    {
        if ($amount > $course->balanceDue() + 0.5) {
            return sprintf('مبلغ بیشتر از مانده‌ی دوره (%s تومان) است.', number_format($course->balanceDue()));
        }
        if ($course->status === 'pending_payment' && $amount + 0.5 < $course->min_prepayment) {
            return sprintf('حداقل پرداخت اول این دوره %s تومان است.', number_format($course->min_prepayment));
        }

        return null;
    }

    private function digits(mixed $value): mixed
    {
        return is_string($value)
            ? strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', ',' => '', '٬' => '', ' ' => ''])
            : $value;
    }
}
