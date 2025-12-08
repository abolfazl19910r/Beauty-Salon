<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\Booking;
use App\Models\SpecialistLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class SpecialistProfileController extends Controller
{

    public function dashboardBookings()
    {
        $user = auth()->user();

        if (!$user->hasRole('specialists')) {
            abort(403, 'شما به این بخش دسترسی ندارید');
        }

        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $bookings = Booking::where('specialist_id', $specialist->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['service', 'user'])
            ->orderBy('booking_time', 'asc')
            ->orderBy('booking_time', 'asc')
            ->paginate(15);

        return view('specialist.dashboard-bookings', [
            'specialist' => $specialist,
            'bookings' => $bookings,
        ]);
    }

    public function show()
    {
        $user = auth()->user();

        if (!$user->hasRole('specialists')) {
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

    public function edit()
    {
        $user = auth()->user();

        if (!$user->hasRole('specialists')) {
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
            ]);

            DB::beginTransaction();

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

            return redirect()->route('specialist.schedule')
                ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function leaves()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $leaves = $specialist->leaves()->latest()->paginate(10);

        return view('specialist.leaves', compact('specialist', 'leaves'));
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

        $status = 'confirmed';
        $booking->update(['status' => $status]);

        return back()->with('success', 'نوبت با موفقیت به عنوان "انجام شده" ثبت شد.');
    }

    public function cancelBooking(Booking $booking)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $booking->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به لغو این نوبت نیستید.');
        }

        $status = 'cancelled';
        $booking->update(['status' => $status]);

        return back()->with('success', 'نوبت با موفقیت لغو شد.');
    }
}
