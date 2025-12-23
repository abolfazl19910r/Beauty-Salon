<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\Booking;
use App\Models\SpecialistLeave;
use App\Notifications\BookingStatusUpdated;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use App\Services\SMSService;
use Illuminate\Support\Facades\Log;

class SpecialistProfileController extends Controller
{

    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function dashboardBookings()
    {
        $user = auth()->user();

        $specialist = Specialist::where('user_id', $user->id)
            ->orWhere('phone', $user->phone)
            ->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $todaySchedule = Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();

        $todayBookingsCount = $todaySchedule->count();

        $todayRevenue = Booking::where('specialist_id', $specialist->id)
            ->whereDate('booking_time', Carbon::today())
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('prepayment_amount');

        $monthBookingsCount = Booking::where('specialist_id', $specialist->id)
            ->whereMonth('booking_time', Carbon::now()->month)
            ->whereYear('booking_time', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->count();

        $averageRating = Booking::where('specialist_id', $specialist->id)
            ->whereNotNull('rating')
            ->avg('rating') ?: 0;

        $upcomingBookings = Booking::where('specialist_id', $specialist->id)
            ->where('booking_time', '>', Carbon::now())
            ->where('booking_time', '<=', Carbon::now()->addDays(7))
            ->where('payment_status', 'paid')
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->get();

        $upcomingBookings->each(function($booking) {

            $booking->booking_date_persian = Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d');

            $booking->status_fa = match($booking->status) {
                'pending'   => 'در انتظار تایید',
                'confirmed' => 'تایید شده',
                'completed' => 'انجام شده',
                'cancelled' => 'لغو شده',
                default     => 'نامشخص'
            };
        });

        $recentReviews = Booking::where('specialist_id', $specialist->id)
            ->whereNotNull('review')
            ->with('user')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $allBookingsCount = Booking::where('specialist_id', $specialist->id)
            ->where('payment_status', 'paid')
            ->count();

        $confirmedBookingsCount = Booking::where('specialist_id', $specialist->id)
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->count();

        $pendingBookingsCount = Booking::where('specialist_id', $specialist->id)
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->count();

        $completedBookingsCount = Booking::where('specialist_id', $specialist->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->count();

        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Booking::where('specialist_id', $specialist->id)
                ->whereDate('booking_time', $date)
                ->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled')
                ->sum('prepayment_amount');

            $weeklyRevenue[] = [
                'date' => Jalalian::fromCarbon($date)->format('m/d'),
                'total' => $revenue
            ];
        }

        return view('specialist.dashboard', compact(
            'specialist',
            'todaySchedule',
            'todayBookingsCount',
            'todayRevenue',
            'monthBookingsCount',
            'averageRating',
            'upcomingBookings',
            'recentReviews',
            'weeklyRevenue',
            'allBookingsCount',
            'confirmedBookingsCount',
            'pendingBookingsCount',
            'completedBookingsCount'
        ));
    }

    public function bookings()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $bookings = Booking::where('specialist_id', $specialist->id)
            ->with(['service', 'user'])
            ->latest()
            ->paginate(10);

        return view('specialist.bookings', compact('specialist', 'bookings'));
    }

    public function show()
    {
        $user = auth()->user();

        if (!$user->hasRole('specialists') && !$user->hasRole('specialist')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }

        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $myBookings = Booking::where('user_id', $user->id)
            ->with(['service', 'specialist'])
            ->latest()
            ->paginate(10);

        $upcomingBookings = Booking::where('user_id', $user->id)
            ->where('booking_time', '>=', Carbon::today())
            ->whereNotIn('status', ['cancelled'])
            ->with(['service', 'specialist'])
            ->orderBy('booking_time')
            ->orderBy('booking_time')
            ->get();

        $totalBookings = Booking::where('user_id', $user->id)->count();
        $completedBookings = Booking::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $cancelledBookings = Booking::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        return view('specialist.profile-show', compact(
            'user',
            'specialist',
            'myBookings',
            'upcomingBookings',
            'totalBookings',
            'completedBookings',
            'cancelledBookings'
        ));
    }

    public function showBooking(Booking $booking)
    {
        $user = auth()->user();

        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما اجازه دسترسی به این نوبت را ندارید.');
        }

        $booking->load(['user', 'service', 'specialist']);

        return view('specialist.booking-show', compact('booking', 'specialist'));
    }

    public function edit()
    {
        $user = auth()->user();

        if (!$user->hasRole('specialists') && !$user->hasRole('specialist')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }

        $specialist = Specialist::where('phone', $user->phone)->first();

        return view('specialist.profile-edit', compact('user', 'specialist'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $specialist = Specialist::where('phone', $request->input('phone', $user->phone))->first();
        if ($specialist) {
            $specialist->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ]);
        }

        return redirect()->route('specialist.profile-show.blade')
            ->with('success', 'اطلاعات پروفایل با موفقیت بروزرسانی شد');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد');
    }

    public function schedule()
    {
        $user = auth()->user();
        if (!$user->hasRole('specialists') && !$user->hasRole('specialist')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $schedules = $specialist->schedules()
            ->get()
            ->groupBy('day_of_week');

        return view('specialist.schedule', compact('specialist', 'schedules'));
    }

    public function updateSchedule(Request $request)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            abort(404, 'رکورد متخصص یافت نشد');
        }

        try {
            $request->validate([
                'schedules.*.day_of_week' => 'required|integer|between:0,6',
                'schedules.*.is_active' => 'nullable',
                'schedules.*.start_time' => 'nullable|required_if:schedules.*.is_active,1',
                'schedules.*.end_time' => 'nullable|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
                'auto_confirm_bookings' => 'nullable|in:0,1',
            ]);

            DB::beginTransaction();

            $specialist->update([
                'auto_confirm_bookings' => $request->input('auto_confirm_bookings', 0) == 1
            ]);

            $specialist->schedules()->delete();

            if ($request->has('schedules')) {
                foreach ($request->schedules as $dayNumber => $schedule) {
                    if (isset($schedule['is_active']) && $schedule['is_active']) {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            $message = 'برنامه کاری با موفقیت بروزرسانی شد';
            if ($specialist->auto_confirm_bookings) {
                $message .= ' - تایید خودکار نوبت‌ها فعال شد';
            }

            return redirect()->route('specialist.profile.show')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function leaves()
    {
        $user = auth()->user();
        if (!$user->hasRole('specialists') && !$user->hasRole('specialist')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $leaves = $specialist->leaves()->latest()->paginate(10);

        return view('specialist.leaves', compact('specialist', 'leaves'));
    }

    public function createLeave()
    {
        $user = auth()->user();
        if (!$user->hasRole('specialists') && !$user->hasRole('specialist')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }

        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        return view('specialist.leaves-create', compact('specialist'));
    }

    public function storeLeave(Request $request)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            abort(404, 'رکورد متخصص یافت نشد');
        }

        try {
            $validated = $request->validate([
                'start_date_jalali' => 'required|string',
                'end_date_jalali' => 'required|string',
                'reason' => 'nullable|string|max:255'
            ]);

            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $startDateEn = str_replace($persianDigits, $englishDigits, $validated['start_date_jalali']);
            $endDateEn = str_replace($persianDigits, $englishDigits, $validated['end_date_jalali']);

            $startDate = Jalalian::fromFormat('Y/m/d', $startDateEn)->toCarbon()->toDateString();
            $endDate = Jalalian::fromFormat('Y/m/d', $endDateEn)->toCarbon()->toDateString();

            $specialist->leaves()->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending'
            ]);

            return redirect()->route('specialist.leaves')
                ->with('success', 'درخواست مرخصی با موفقیت ثبت شد و در انتظار تایید است');

        } catch (\Exception $e) {
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function destroyLeave(SpecialistLeave $leave)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $leave->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به حذف این مرخصی نیستید');
        }

        if ($leave->status !== 'pending') {
            return back()->with('error', 'فقط مرخصی‌های در انتظار تایید قابل حذف هستند');
        }

        $leave->delete();

        return redirect()->route('specialist.leaves')
            ->with('success', 'درخواست مرخصی با موفقیت حذف شد');
    }

    public function completeBooking(Booking $booking)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به تغییر وضعیت این نوبت نیستید.');
        }

        if ($booking->status === 'confirmed') {
            return back()->with('info', 'این نوبت قبلاً تایید شده است.');
        }

        try {
            DB::transaction(function() use ($booking) {
                $booking->update(['status' => 'confirmed']);

                $booking->user->notify(new \App\Notifications\BookingStatusUpdated($booking, 'confirmed'));
            });

            return back()->with('success', '✓ نوبت تایید شد و پیامک اطلاع‌رسانی ارسال گردید.');
        } catch (Exception $e) {
            Log::error('خطا در تایید نوبت توسط متخصص', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'خطا در تایید نوبت: ' . $e->getMessage());
        }
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به لغو این نوبت نیستید.');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('info', 'این نوبت قبلاً لغو شده است.');
        }

        $cancelReason = $request->input('cancel_reason', 'دلیل مشخص نشده');

        try {
            DB::transaction(function () use ($booking, $cancelReason) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $cancelReason,
                    'cancelled_by' => 'specialist',
                    'cancelled_at' => now()
                ]);

                $booking->user->notify(new BookingStatusUpdated($booking, 'cancelled', $cancelReason));
            });

            return back()->with('success', '✓ نوبت لغو و به مشتری اطلاع‌رسانی شد.');
        } catch (Exception $e) {
            Log::error('خطا در لغو نوبت توسط متخصص', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'خطا در لغو نوبت: ' . $e->getMessage());
        }
    }

    public function notifications()
    {
        $user = auth()->user();

        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('specialist.notifications', compact('specialist', 'notifications'));
    }

    public function latestNotifications()
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->data['message'] ?? 'اعلان جدید',
                    'link' => $notification->data['link'] ?? route('specialist.my-dashboard'),
                    'read_at' => $notification->read_at,
                    'time_ago' => $this->timeAgo($notification->created_at),
                ];
            });

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    public function notificationsCount()
    {
        $user = auth()->user();

        $count = $user->unreadNotifications()->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function markNotificationAsRead($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()->find($id);

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true
        ]);
    }

    private function timeAgo($datetime)
    {
        $now = Carbon::now();
        $diff = $datetime->diffInSeconds($now);

        if ($diff < 60) {
            return 'لحظاتی پیش';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' دقیقه پیش';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ساعت پیش';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' روز پیش';
        } else {
            return Jalalian::fromCarbon($datetime)->format('Y/m/d');
        }
    }
}
