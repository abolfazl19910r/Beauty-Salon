<?php

namespace App\Http\Controllers\Specialist\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\UpdateScheduleRequest;
use App\Http\Requests\Specialist\UpdateSpecialistPasswordRequest;
use App\Http\Requests\Specialist\UpdateSpecialistProfileRequest;
use App\Http\Requests\Specialist\UpdateWorkScheduleRequest;
use App\Models\LoyaltyPoint;
use App\Models\Specialist;
use App\Services\Specialist\WorkScheduleService;
use App\Services\SpecialistDashboardService;
use App\Services\SpecialistProfileService;
use App\Traits\ResolvesSpecialist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SpecialistProfileController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(
        protected SpecialistDashboardService $dashboardService,
        protected SpecialistProfileService $profileService,
        protected WorkScheduleService $workScheduleService,
    ) {}

    public function dashboard()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $data = $this->dashboardService->getDashboardData($specialist);

        return view('specialist.dashboard', array_merge(['specialist' => $specialist], $data));
    }

    public function show()
    {
        $user = auth()->user();
        $specialist = $this->resolveSpecialist(orFail: true);

        $profileData = $this->profileService->getProfileShowData($user);

        return view('specialist.profile-show', array_merge(
            compact('user', 'specialist'),
            $profileData
        ));
    }

    public function edit()
    {
        $user = auth()->user();
        $specialist = $this->resolveSpecialist(orFail: true);

        return view('specialist.profile-edit', compact('user', 'specialist'));
    }
    public function update(UpdateSpecialistProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $specialist = Specialist::where('phone', $validated['phone'])->first();
        $specialist?->update([
            'name'  => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        return redirect()->route('specialist.profile.show')
            ->with('success', 'اطلاعات پروفایل با موفقیت بروزرسانی شد.');
    }

    public function updatePassword(UpdateSpecialistPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }

    public function schedule()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $schedules = $specialist->schedules()->get()->groupBy('day_of_week');

        return view('specialist.schedule', compact('specialist', 'schedules'));
    }

    public function updateSchedule(UpdateScheduleRequest $request): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            abort(404, 'رکورد متخصص یافت نشد.');
        }

        $this->authorize('manageSchedule', $specialist);

        try {
            DB::transaction(function () use ($request, $specialist) {
                $specialist->update([
                    'auto_confirm_bookings' => $request->input('auto_confirm_bookings', 0) == 1,
                ]);

                $specialist->schedules()->delete();

                foreach ($request->input('schedules', []) as $schedule) {
                    if (! empty($schedule['is_active'])) {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time'  => $schedule['start_time'],
                            'end_time'    => $schedule['end_time'],
                            'is_active'   => true,
                        ]);
                    }
                }
            });

            $message = 'برنامه کاری با موفقیت بروزرسانی شد.';
            if ($specialist->fresh()->auto_confirm_bookings) {
                $message .= ' تایید خودکار نوبت‌ها فعال شد.';
            }

            return redirect()->route('specialist.my-dashboard')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('خطا در بروزرسانی برنامه کاری', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    /**
     * Show WorkSchedule for self-service.
     * * Uses the same Service as admin — DRY.
 */
    public function workSchedule()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $this->authorize('manageSchedule', $specialist);

        $schedule = $specialist->workSchedule;

        return view('specialist.work-schedule', compact('specialist', 'schedule'));
    }

    public function updateWorkSchedule(UpdateWorkScheduleRequest $request): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            abort(404, 'رکورد متخصص یافت نشد.');
        }

        $this->authorize('manageSchedule', $specialist);

        try {
            $this->workScheduleService->upsert($specialist, $request->validated());

            return redirect()->route('specialist.work-schedule')
                ->with('success', 'برنامه کاری با موفقیت ذخیره شد.');
        } catch (\Exception $e) {
            Log::error('خطا در بروزرسانی برنامه کاری (WorkSchedule)', ['error' => $e->getMessage()]);

            return back()->with('error', 'خطا در ذخیره اطلاعات رخ داد.');
        }
    }

    public function destroyWorkSchedule(): RedirectResponse
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            abort(404, 'رکورد متخصص یافت نشد.');
        }

        $this->authorize('manageSchedule', $specialist);

        $this->workScheduleService->delete($specialist);

        return redirect()->route('specialist.work-schedule')
            ->with('success', 'برنامه کاری حذف شد.');
    }

    public function loyalty()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $user = auth()->user();
        $currentBalance  = LoyaltyPoint::getCurrentBalance($user->id);
        $expiringPoints  = LoyaltyPoint::getExpiringPoints($user->id, 30);

        $history = LoyaltyPoint::where('user_id', $user->id)
            ->with(['booking' => fn ($q) => $q->select('id', 'booking_time', 'service_id', 'specialist_id')
                ->with(['service:id,name', 'specialist:id,name'])])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('specialist.loyalty', compact('specialist', 'currentBalance', 'expiringPoints', 'history'));
    }

}
