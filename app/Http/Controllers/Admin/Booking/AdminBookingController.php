<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Exceptions\BookingNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\StoreAdminBookingRequest;
use App\Http\Requests\Admin\Booking\UpdateAdminBookingRequest;
use App\Models\Booking;
use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\Booking\AdminBookingService;
use App\Services\Booking\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function __construct(
        protected readonly AdminBookingService $bookingService,
        protected readonly BookingService $sharedBookingService,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly BeautyServiceRepositoryInterface $beautyServiceRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => ($request->has('status') && $request->status !== '') ? $request->status : null,
            'date' => $request->filled('date') ? $request->date : null,
        ];

        $bookings = $this->bookingRepository->paginateWithFilters($filters, 15);
        $stats = $this->bookingRepository->getStats($filters);
        $hasDateFilter = $request->filled('date');

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'totalBookings' => $stats['total'],
            'confirmedBookings' => $stats['confirmed'],
            'cancelledBookings' => $stats['cancelled'],
            'hasDateFilter' => $hasDateFilter,
        ]);
    }

    public function create(): View
    {
        $services = $this->beautyServiceRepository->all();
        $specialists = $this->specialistRepository->all();

        return view('admin.bookings.create', compact('services', 'specialists'));
    }

    public function store(StoreAdminBookingRequest $request): RedirectResponse
    {
        // مشتری دوره‌ی فعالِ همین خدمت را دارد ← جلسه‌ای از همان دوره (بسته‌ی ب؛ تیک «از دوره کم شود»، پیش‌فرض روشن). مدیر فاصله‌ی
        // حداقل را دور می‌زند؛ مهلت و پول دوره همچنان رعایت می‌شوند.
        if ($request->boolean('use_course')) {
            $course = \App\Models\CustomerCourse::query()->where('user_id', $request->validated('user_id'))
                ->where('service_id', $request->validated('service_id'))->where('status', 'active')->orderBy('id')->get()
                ->first(fn (\App\Models\CustomerCourse $c) => $c->bookingBlocker() === null);

            if ($course) {
                try {
                    $booking = app(\App\Services\Courses\CourseService::class)->bookSession(
                        $course, (int) $request->validated('specialist_id'), (string) $request->validated('booking_time'), byStaff: true, source: (string) $request->validated('source'),
                    );
                } catch (\App\Services\Courses\CourseException $e) {
                    return back()->withInput()->with('error', $e->getMessage());
                } catch (BookingNotAvailableException $e) {
                    return back()->withInput()->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
                }

                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', sprintf('نوبت به‌عنوان %s دوره‌ی «%s» ثبت شد (بدون پرداخت).', $course->sessionLabel((int) $booking->course_session_number), $course->name));
            }
        }

        try {
            $booking = $this->sharedBookingService->createManualBooking($request->validated());
        } catch (BookingNotAvailableException $e) {
            return back()->withInput()
                ->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
        }

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    public function edit(Booking $booking): View
    {
        $this->ensureSalonOwnership($booking->salon_id);

        $users = $this->userRepository->getSalonCustomerOptions();
        $services = $this->beautyServiceRepository->all();
        $specialists = $this->specialistRepository->all();

        return view('admin.bookings.edit', compact('booking', 'users', 'services', 'specialists'));
    }

    public function show(Booking $booking): View
    {
        $this->ensureSalonOwnership($booking->salon_id);

        $booking->load(['service', 'user', 'specialist']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function update(UpdateAdminBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->ensureSalonOwnership($booking->salon_id);

        $redirectRoute = $request->isStatusOnly() ? 'admin.bookings.index' : 'admin.bookings.show';
        $redirectParams = $request->isStatusOnly() ? [] : ['booking' => $booking->id];

        try {
            $result = $request->isStatusOnly()
                ? $this->bookingService->updateStatus($booking, $request->validated()['status'])
                : $this->bookingService->updateFull($booking, $request->validated());

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('success', $result['message']);

        } catch (BookingNotAvailableException $e) {
            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
        } catch (\Exception $e) {
            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'خطایی در بروزرسانی وضعیت نوبت رخ داد. لطفا مجددا تلاش کنید.');
        }
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->ensureSalonOwnership($booking->salon_id);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('admin.bookings.index')
                ->with('error', 'نوبت‌های پرداخت شده را نمی‌توان حذف کرد. ابتدا آن را لغو کنید.');
        }

        $this->bookingRepository->delete($booking);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'نوبت با موفقیت حذف شد.');
    }
}
