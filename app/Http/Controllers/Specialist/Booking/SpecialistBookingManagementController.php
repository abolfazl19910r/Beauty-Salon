<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\Completed\BookingCompleted;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Traits\HasJalaliDates;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SpecialistBookingManagementController extends Controller
{
    use HasJalaliDates;
    use ResolvesSpecialist;

    public function __construct(private readonly BookingRepositoryInterface $bookingRepository) {}

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
        $specialist = $this->resolveSpecialist();

        if (! $specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به تغییر وضعیت این نوبت نیستید.');
        }

        if ($booking->status === 'confirmed') {
            return back()->with('info', 'این نوبت قبلاً تایید شده است.');
        }

        // ⭐ Fix (۲۰۲۶-۱۰-۱۰، بسته‌ی ۲ اپلیکیشن): پذیرش فقط از «در انتظار تأیید». قبلاً هر وضعیتی confirmed می‌شد —
        // نوبت لغوشده (پول برگشت‌خورده) زنده می‌شد، pending_payment (مشتری هنوز در درگاه) بدون پرداخت confirmed و از دید
        // لغو خودکار پرداخت‌نشده‌ها خارج می‌شد، و completed به confirmed برمی‌گشت. دکمه فقط برای pending نمایش داده می‌شد،
        // ولی سرور چک نمی‌کرد. وضعیت داخل تراکنش با قفل ردیف دوباره خوانده می‌شود (لغو هم‌زمان مشتری).
        if ($booking->status !== 'pending') {
            return back()->with('error', 'فقط نوبت‌های «در انتظار تأیید» قابل پذیرش هستند.');
        }

        try {
            $confirmed = DB::transaction(function () use ($booking) {
                $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== 'pending') {
                    return false;
                }

                $locked->update(['status' => 'confirmed']);
                $locked->user->notify(new \App\Notifications\Booking\BookingStatusUpdated($locked, 'confirmed'));

                return true;
            });

            if (! $confirmed) {
                return back()->with('error', 'وضعیت این نوبت تغییر کرده است؛ صفحه را دوباره باز کنید.');
            }

            return back()->with('success', '✓ نوبت تایید شد و پیامک اطلاع‌رسانی ارسال گردید.');

        } catch (Exception $e) {
            Log::error('خطا در تایید نوبت', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در تایید نوبت: '.$e->getMessage());
        }
    }

    public function markAsCompleted(Booking $booking): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if ($booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به تغییر وضعیت این نوبت نیستید.');
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'فقط نوبت‌های تایید شده قابل علامت‌گذاری به عنوان «انجام شده» هستند.');
        }

        try {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'completed']);
            });

            event(new BookingCompleted($booking));

            return back()->with('success', '✅ نوبت به عنوان انجام شده علامت‌گذاری شد.');

        } catch (\Exception $e) {
            Log::error('خطا در علامت‌گذاری نوبت', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در علامت‌گذاری نوبت: '.$e->getMessage());
        }
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به لغو این نوبت نیستید.');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('info', 'این نوبت قبلاً لغو شده است.');
        }

        if ($booking->status === 'completed') {
            return back()->with('error', 'نوبت‌های انجام شده قابل لغو نیستند.');
        }

        try {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->input('cancel_reason', 'دلیل مشخص نشده'),
                'cancelled_by' => 'specialist',
                'cancelled_at' => now(),
            ]);

            event(new BookingCancelled($booking, 'specialist'));

            return back()->with('success', '✓ نوبت لغو و به مشتری اطلاع‌رسانی شد.');

        } catch (Exception $e) {
            Log::error('خطا در لغو نوبت توسط متخصص', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در لغو نوبت: '.$e->getMessage());
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
