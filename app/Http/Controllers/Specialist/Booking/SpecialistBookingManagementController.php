<?php

namespace App\Http\Controllers\Specialist\Booking;

use App\Events\Booking\BookingCancelled;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Review\ReviewService;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class SpecialistBookingManagementController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(
        protected ReviewService $reviewService,
    ) {}

    public function index(Request $request)
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageBookings', $specialist);

        $query = Booking::where('specialist_id', $specialist->id)
            ->with(['service', 'user']);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->get('sort_by', 'latest'));

        $bookings = $query->paginate(10)->withQueryString();

        return view('specialist.bookings', compact('specialist', 'bookings'));
    }

    public function show(Booking $booking)
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

        try {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'confirmed']);
                $booking->user->notify(new \App\Notifications\Booking\BookingStatusUpdated($booking, 'confirmed'));
            });

            return back()->with('success', '✓ نوبت تایید شد و پیامک اطلاع‌رسانی ارسال گردید.');

        } catch (Exception $e) {
            Log::error('خطا در تایید نوبت', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در تایید نوبت: ' . $e->getMessage());
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

                try {
                    $this->reviewService->sendReviewRequest($booking);
                } catch (\Exception $e) {
                    Log::error('خطا در ارسال درخواست نظرسنجی', [
                        'booking_id' => $booking->id,
                        'error'      => $e->getMessage(),
                    ]);
                }

                $booking->user->notify(new \App\Notifications\Booking\BookingStatusUpdated($booking, 'completed'));
            });

            return back()->with('success', '✅ نوبت به عنوان انجام شده علامت‌گذاری شد.');

        } catch (\Exception $e) {
            Log::error('خطا در علامت‌گذاری نوبت', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در علامت‌گذاری نوبت: ' . $e->getMessage());
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
                'status'              => 'cancelled',
                'cancellation_reason' => $request->input('cancel_reason', 'دلیل مشخص نشده'),
                'cancelled_by'        => 'specialist',
                'cancelled_at'        => now(),
            ]);

            event(new BookingCancelled($booking, 'specialist'));

            return back()->with('success', '✓ نوبت لغو و به مشتری اطلاع‌رسانی شد.');

        } catch (Exception $e) {
            Log::error('خطا در لغو نوبت توسط متخصص', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'خطا در لغو نوبت: ' . $e->getMessage());
        }
    }

    private function applyFilters($query, Request $request): void
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        if ($request->filled('date_from')) {
            try {
                $dateFrom = str_replace($persianDigits, $englishDigits, $request->date_from);
                $query->where('booking_time', '>=', Jalalian::fromFormat('Y/m/d', $dateFrom)->toCarbon()->startOfDay());
            } catch (\Exception $e) {
                Log::warning('خطا در تبدیل تاریخ از: ' . $e->getMessage());
            }
        }

        if ($request->filled('date_to')) {
            try {
                $dateTo = str_replace($persianDigits, $englishDigits, $request->date_to);
                $query->where('booking_time', '<=', Jalalian::fromFormat('Y/m/d', $dateTo)->toCarbon()->endOfDay());
            } catch (\Exception $e) {
                Log::warning('خطا در تبدیل تاریخ تا: ' . $e->getMessage());
            }
        }

        foreach (['time', 'status', 'payment_status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter === 'time' ? 'booking_time' : $filter, $request->$filter);
            }
        }

        if ($request->filled('phone')) {
            $query->whereHas('user', fn ($q) => $q->where('phone', 'like', '%' . $request->phone . '%'));
        }

        if ($request->filled('customer_name')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $request->customer_name . '%'));
        }
    }

    private function applySort($query, string $sortBy): void
    {
        match ($sortBy) {
            'oldest', 'date_asc' => $query->orderBy('booking_time', 'asc'),
            'date_desc'          => $query->orderBy('booking_time', 'desc'),
            default              => $query->latest(),
        };
    }
}
