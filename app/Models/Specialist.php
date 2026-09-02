<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class Specialist extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use BelongsToSalon;

    protected $fillable = [
        'name', 'phone', 'user_id', 'email', 'auto_confirm_bookings',
        'commission_rate',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'auto_confirm_bookings' => 'boolean',
        'commission_rate' => 'float',
    ];

    public static function latest()
    {
        return self::orderBy('created_at', 'desc');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SpecialistSchedule::class);
    }

    /**
     * ⭐ Migrated from SpecialistLeave to Leave (fuller version: approve/reject
     * * with accurate time recording, rejection reason, and conflict check). SpecialistLeave/leaves table
     * * Physically the same as before; only the model class has changed.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'specialist_id');
    }

    /**
     * ⭐ Commit 4b-3 (feat/saas-multi-tenant-salons): added alongside EnsureSpecialistSalonActive,
     * which is the first place this relation was actually needed.
     */
    public function salon(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Salon::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(\App\Models\Review::class, 'specialist_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BeautyService::class, 'specialist_services');
    }

    public function getWorkDaysAttribute(): string
    {
        if ($this->relationLoaded('schedules')) {
            $activeSchedules = $this->schedules
                ->where('is_active', true)
                ->sortBy('day_of_week');
        } else {
            $activeSchedules = $this->schedules()
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->get();
        }

        if ($activeSchedules->isEmpty()) {
            return 'تعریف نشده';
        }

        $persianDays = [
            0 => 'یکشنبه',
            1 => 'دوشنبه',
            2 => 'سه‌شنبه',
            3 => 'چهارشنبه',
            4 => 'پنج‌شنبه',
            5 => 'جمعه',
            6 => 'شنبه',
        ];

        $workDays = $activeSchedules->map(function ($schedule) use ($persianDays) {
            return $persianDays[$schedule->day_of_week] ?? '';
        })->filter()->values();

        if ($workDays->count() === 7) {
            return 'تمام روزهای هفته';
        }

        return $workDays->implode('، ');
    }

    public function getWorkHoursForDay(int $dayOfWeek): ?array
    {
        $schedule = $this->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $schedule) {
            return null;
        }

        return [
            'start' => $schedule->start_time,
            'end' => $schedule->end_time,
            'break_start' => $schedule->break_start,
            'break_end' => $schedule->break_end,
        ];
    }

    /**
     * @param  int|null  $excludeBookingId  ⭐ Fix (fix/admin-booking-slot-conflict, commit 4):
     *      when re-checking availability for a booking that ALREADY occupies this specialist's
     *      calendar (i.e. editing an existing booking rather than creating a new one), that
     *      booking's own row must be excluded from the "existing bookings" query below —
     *      otherwise a booking always collides with itself and every edit that doesn't change
     *      the time would be incorrectly rejected as "slot taken". Left null (default) for the
     *      normal create-flow callers, which are unaffected by this addition.
     */
    public function getAvailableSlots($date, $serviceDuration = null, ?int $excludeBookingId = null): array
    {
        try {
            $carbonDate = Carbon::parse($date);
            $now = Carbon::now();

            if ($carbonDate->lt(Carbon::today())) {
                return [];
            }

            $hasLeave = $this->leaves()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists();

            if ($hasLeave) {
                return [];
            }

            $isHoliday = $this->holidays()->whereDate('date', $carbonDate)->exists();
            if ($isHoliday) {
                return [];
            }

            $schedule = $this->schedules()
                ->where('day_of_week', $carbonDate->dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (! $schedule) {
                return [];
            }

            $duration = $serviceDuration ?? 30;

            $existingBookings = $this->bookings()
                ->whereDate('booking_time', $date)
                ->where('status', '!=', 'cancelled')
                ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
                ->with('service')
                ->get()
                ->map(fn ($b) => [
                    'start' => Carbon::parse($b->booking_time),
                    'end' => Carbon::parse($b->booking_time)->addMinutes($b->service->duration ?? 30),
                ]);

            $slots = [];
            $currentTime = Carbon::parse($date.' '.$schedule->start_time);
            $endTime = Carbon::parse($date.' '.$schedule->end_time);

            while ($currentTime->copy()->addMinutes($duration)->lte($endTime)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $slotStart->copy()->addMinutes($duration);

                if ($slotStart->lte($now)) {
                    $currentTime->addMinutes(30);

                    continue;
                }

                $isInBreak = false;
                if ($schedule->break_start && $schedule->break_end) {
                    $breakStart = Carbon::parse($date.' '.$schedule->break_start);
                    $breakEnd = Carbon::parse($date.' '.$schedule->break_end);

                    if ($slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                        $currentTime = $breakEnd->copy();
                        $isInBreak = true;
                    }
                }
                if ($isInBreak) {
                    continue;
                }

                $conflict = $existingBookings->first(fn ($b) => $slotStart->lt($b['end']) && $slotEnd->gt($b['start']));

                if (! $conflict) {
                    $slots[] = $slotStart->format('H:i');
                    $currentTime->addMinutes($duration);
                } else {
                    $currentTime = $conflict['end']->copy();
                }
            }

            return $slots;
        } catch (\Exception $e) {
            Log::error('Slot calculation error: '.$e->getMessage());

            return [];
        }
    }

    public function isAvailable($dateTime, $serviceDuration = null): bool
    {
        $date = Carbon::parse($dateTime)->toDateString();
        $time = Carbon::parse($dateTime)->format('H:i');

        $availableSlots = $this->getAvailableSlots($date, $serviceDuration);

        return in_array($time, $availableSlots);
    }

    public function getMonthAvailability($yearMonth): array
    {
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $result = [
            'available_days' => [],
            'fully_booked_days' => [],
            'holiday_days' => [],
        ];

        $schedules = $this->schedules()->where('is_active', true)->get()->keyBy('day_of_week');

        if ($schedules->isEmpty()) {
            return $result;
        }

        $holidays = $this->holidays()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $leaves = $this->leaves()
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })->get();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $startDate->copy()->setDay($day);
            $dateString = $currentDate->toDateString();

            if (in_array($dateString, $holidays)) {
                $result['holiday_days'][] = $dateString;

                continue;
            }

            $isOnLeave = $leaves->contains(function ($leave) use ($currentDate) {
                return $currentDate->between($leave->start_date, $leave->end_date);
            });

            if ($isOnLeave) {
                $result['holiday_days'][] = $dateString;

                continue;
            }

            if (! $schedules->has($currentDate->dayOfWeek)) {
                $result['fully_booked_days'][] = $dateString;

                continue;
            }

            $availableSlots = $this->getAvailableSlots($dateString);

            if (empty($availableSlots)) {
                $result['fully_booked_days'][] = $dateString;
            } else {
                $result['available_days'][] = [
                    'date' => $dateString,
                    'slots_count' => count($availableSlots),
                ];
            }
        }

        return $result;
    }

    public function hasAutoConfirm(): bool
    {
        return $this->auto_confirm_bookings === true;
    }

    public function notifications()
    {
        return $this->morphMany(
            \App\Models\UserNotification::class,
            'notifiable'
        )->orderBy('created_at', 'desc');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(SpecialistWallet::class);
    }

    public function getOrCreateWallet(): SpecialistWallet
    {
        if (! $this->wallet) {
            $this->wallet()->create([
                'balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_amount' => 0,
            ]);
            $this->load('wallet');
        }

        return $this->wallet;
    }

    public function getEffectiveCommissionRate(): float
    {
        if (! is_null($this->commission_rate)) {
            return (float) $this->commission_rate;
        }

        $settings = \App\Models\WalletSetting::first();

        return (float) ($settings->admin_commission_percentage ?? 10);
    }
}
