<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Specialist extends Model
{
    use Notifiable;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'user_id',
        'email',
        'auto_confirm_bookings'
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'auto_confirm_bookings' => 'boolean',
    ];

    protected $appends = ['work_days'];

    public static function latest()
    {
        return self::orderBy('created_at', 'desc');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SpecialistSchedule::class);
    }

    public function workSchedule(): HasOne
    {
        return $this->hasOne(WorkSchedule::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(SpecialistLeave::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'specialist_id');
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

        $workDays = $activeSchedules->map(function($schedule) use ($persianDays) {
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

        if (!$schedule) {
            return null;
        }

        return [
            'start' => $schedule->start_time,
            'end' => $schedule->end_time,
            'break_start' => $schedule->break_start,
            'break_end' => $schedule->break_end,
        ];
    }

    public function getAvailableSlots($date, $serviceDuration = null): array
    {
        try {
            $carbonDate = Carbon::parse($date);
            $now = Carbon::now();

            if ($carbonDate->lt(Carbon::today())) {
                return [];
            }

            // ۱. بهینه‌سازی: استفاده از Relation Loaded برای مرخصی و تعطیلات (اگر قبلا در ایندکس لود شده باشند)
            $hasLeave = $this->leaves()
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists();

            if ($hasLeave) return [];

            $isHoliday = $this->holidays()->whereDate('date', $carbonDate)->exists();
            if ($isHoliday) return [];

            // ۲. دریافت برنامه کاری
            $schedule = $this->schedules()
                ->where('day_of_week', $carbonDate->dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) return [];

            $duration = $serviceDuration ?? 30;

            // ۳. دریافت رزروها با Eager Loading سرویس برای دانستن مدت زمان هر رزرو
            $existingBookings = $this->bookings()
                ->whereDate('booking_time', $date)
                ->where('status', '!=', 'cancelled')
                ->with('service')
                ->get()
                ->map(fn($b) => [
                    'start' => Carbon::parse($b->booking_time),
                    'end' => Carbon::parse($b->booking_time)->addMinutes($b->service->duration ?? 30)
                ]);

            $slots = [];
            $currentTime = Carbon::parse($date . ' ' . $schedule->start_time);
            $endTime = Carbon::parse($date . ' ' . $schedule->end_time);

            while ($currentTime->copy()->addMinutes($duration)->lte($endTime)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $slotStart->copy()->addMinutes($duration);

                // بررسی زمان گذشته
                if ($slotStart->lte($now)) {
                    $currentTime->addMinutes(30);
                    continue;
                }

                // بررسی تداخل با استراحت
                $isInBreak = false;
                if ($schedule->break_start && $schedule->break_end) {
                    $breakStart = Carbon::parse($date . ' ' . $schedule->break_start);
                    $breakEnd = Carbon::parse($date . ' ' . $schedule->break_end);

                    if ($slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                        $currentTime = $breakEnd->copy(); // پرش به بعد از استراحت
                        $isInBreak = true;
                    }
                }
                if ($isInBreak) continue;

                // بررسی تداخل با رزروهای موجود
                $conflict = $existingBookings->first(fn($b) => $slotStart->lt($b['end']) && $slotEnd->gt($b['start']));

                if (!$conflict) {
                    $slots[] = $slotStart->format('H:i');
                    $currentTime->addMinutes(30); // یا addMinutes($duration) بسته به مدل بیزنس شما
                } else {
                    // بهینه‌سازی: پرش به پایانِ رزروری که تداخل ایجاد کرده
                    $currentTime = $conflict['end']->copy();
                }
            }

            return $slots;
        } catch (\Exception $e) {
            Log::error("Slot calculation error: " . $e->getMessage());
            return [];
        }
    }

    /**
     *
     * @param string $dateTime
     * @param int|null $serviceDuration
     * @return bool
     */
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
            'holiday_days' => []
        ];

        $schedules = $this->schedules()->where('is_active', true)->get()->keyBy('day_of_week');

        if ($schedules->isEmpty()) {
            return $result;
        }

        $holidays = $this->holidays()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $leaves = $this->leaves()
            ->where('status', 'approved')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
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

            $isOnLeave = $leaves->contains(function($leave) use ($currentDate) {
                return $currentDate->between($leave->start_date, $leave->end_date);
            });

            if ($isOnLeave) {
                $result['holiday_days'][] = $dateString;
                continue;
            }

            if (!$schedules->has($currentDate->dayOfWeek)) {
                $result['fully_booked_days'][] = $dateString;
                continue;
            }

            $availableSlots = $this->getAvailableSlots($dateString);

            if (empty($availableSlots)) {
                $result['fully_booked_days'][] = $dateString;
            } else {
                $result['available_days'][] = [
                    'date' => $dateString,
                    'slots_count' => count($availableSlots)
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
}
