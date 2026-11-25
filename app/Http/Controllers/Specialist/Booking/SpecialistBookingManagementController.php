<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Exceptions\InvalidBookingStateException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Specialist\SpecialistBookingActions;
use App\Services\Specialist\SpecialistBookingListFilter;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecialistBookingManagementController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(
        private readonly SpecialistBookingActions $bookingActions,
    ) {}

    public function index(Request $request): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageBookings', $specialist);

        // بسته‌ی ۳ ظاهری (۲۰۲۶-۱۱-۲۵): تب + یک جستجو جای فرم فیلتر تاریخ/ساعت/وضعیت/پرداخت
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(array_keys(SpecialistBookingListFilter::TABS))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $tab = $filters['tab'] ?? SpecialistBookingListFilter::DEFAULT_TAB;
        $search = $filters['q'] ?? null;

        return view('specialist.bookings', [
            'specialist' => $specialist,
            'tab' => $tab,
            'search' => $search,
            'tabCounts' => SpecialistBookingListFilter::tabCounts($specialist, $search),
            'waitingCount' => SpecialistBookingListFilter::waitingCount($specialist),
            'bookings' => SpecialistBookingListFilter::paginate($specialist, $tab, $search),
        ]);
    }

    public function show(Booking $booking): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما اجازه دسترسی به این نوبت را ندارید.');
        }

        $booking->load(['user', 'service', 'specialist', 'customerCourse']);

        // جلسه‌ی دوره‌ی درمانی (بسته‌ی ب): جلسه‌های قبلی همان دوره با یادداشت و عکس
        $courseSessions = collect();
        $courseRecords = ['notes' => collect(), 'photos' => collect()];
        if ($booking->customerCourse) {
            $courseSessions = $booking->customerCourse->bookings()->with('specialist')->orderBy('booking_time')->get();
            $courseRecords = app(\App\Services\Courses\CourseSessionRecords::class)->recordsFor($booking->customerCourse);
        }

        return view('specialist.booking-show', compact('booking', 'specialist', 'courseSessions', 'courseRecords'));
    }

    public function complete(Booking $booking): RedirectResponse
    {
        $this->authorizeOwnBooking($booking, 'شما مجاز به تغییر وضعیت این نوبت نیستید.');

        // پذیرش فقط از «در انتظار تأیید» (رفع ۲۰۲۶-۱۰-۱۰) — منطق مشترک با API در SpecialistBookingActions
        return $this->run(
            fn () => $this->bookingActions->confirm($booking),
            'خطا در تایید نوبت',
            $booking,
            '✓ نوبت تایید شد و پیامک اطلاع‌رسانی ارسال گردید.',
            'این نوبت قبلاً تایید شده است.',
        );
    }

    public function markAsCompleted(Booking $booking): RedirectResponse
    {
        $this->authorizeOwnBooking($booking, 'شما مجاز به تغییر وضعیت این نوبت نیستید.');

        return $this->run(
            fn () => $this->bookingActions->complete($booking),
            'خطا در علامت‌گذاری نوبت',
            $booking,
            '✅ نوبت به عنوان انجام شده علامت‌گذاری شد.',
            'این نوبت قبلاً انجام شده است.',
        );
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwnBooking($booking, 'شما مجاز به لغو این نوبت نیستید.');

        return $this->run(
            fn () => $this->bookingActions->cancel($booking, $request->input('cancel_reason')),
            'خطا در لغو نوبت',
            $booking,
            '✓ نوبت لغو و به مشتری اطلاع‌رسانی شد.',
            'این نوبت قبلاً لغو شده است.',
        );
    }

    private function authorizeOwnBooking(Booking $booking, string $message): void
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, $message);
        }
    }

    /**
     * @param  callable(): bool  $action  true = وضعیت عوض شد، false = از قبل در وضعیت مقصد بود
     */
    private function run(callable $action, string $errorLabel, Booking $booking, string $success, string $already): RedirectResponse
    {
        try {
            return $action()
                ? back()->with('success', $success)
                : back()->with('info', $already);
        } catch (InvalidBookingStateException $e) {
            return back()->with('error', $e->getUserMessage());
        } catch (Exception $e) {
            Log::error($errorLabel, ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', $errorLabel.': '.$e->getMessage());
        }
    }
}
