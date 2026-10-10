<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Exceptions\InvalidBookingStateException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Specialist\SpecialistBookingActions;
use App\Traits\HasJalaliDates;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SpecialistBookingManagementController extends Controller
{
    use HasJalaliDates;
    use ResolvesSpecialist;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly SpecialistBookingActions $bookingActions,
    ) {}

    public function index(Request $request): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageBookings', $specialist);

        $query = $this->bookingRepository->query()
            ->where('specialist_id', $specialist->id)
            ->with(['service', 'user']);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->get('sort_by', 'latest'));

        $bookings = $query->paginate(10)->withQueryString();

        return view('specialist.bookings', compact('specialist', 'bookings'));
    }

    public function show(Booking $booking): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما اجازه دسترسی به این نوبت را ندارید.');
        }

        $booking->load(['user', 'service', 'specialist']);

        return view('specialist.booking-show', compact('booking', 'specialist'));
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

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            if ($dateFrom = $this->parseJalali($request->date_from, context: 'تاریخ از')) {
                $query->where('booking_time', '>=', $dateFrom->startOfDay());
            }
        }

        if ($request->filled('date_to')) {
            if ($dateTo = $this->parseJalali($request->date_to, context: 'تاریخ تا')) {
                $query->where('booking_time', '<=', $dateTo->endOfDay());
            }
        }

        foreach (['time', 'status', 'payment_status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter === 'time' ? 'booking_time' : $filter, $request->$filter);
            }
        }

        if ($request->filled('phone')) {
            $query->whereHas('user', fn ($q) => $q->where('phone', 'like', '%'.$request->phone.'%'));
        }

        if ($request->filled('customer_name')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$request->customer_name.'%'));
        }
    }

    private function applySort($query, string $sortBy): void
    {
        match ($sortBy) {
            'oldest', 'date_asc' => $query->orderBy('booking_time', 'asc'),
            'date_desc' => $query->orderBy('booking_time', 'desc'),
            default => $query->latest(),
        };
    }
}
