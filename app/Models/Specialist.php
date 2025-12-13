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

    public function getAvailableSlots($date): array
    {
        try {
            $carbonDate = Carbon::parse($date);
            $now = Carbon::now();

            if ($carbonDate->lt(Carbon::today())) {
                return [];
            }

            $hasLeave = $this->leaves()
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('status', 'approved')
                ->exists();

            $isHoliday = $this->holidays()
                ->whereDate('date', $carbonDate)
                ->exists();

            if ($hasLeave || $isHoliday) {
                return [];
            }

            $schedule = $this->schedules()
                ->where('day_of_week', $carbonDate->dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                return [];
            }

            $bookedTimes = $this->bookings()
                ->whereDate('booking_time', $date)
                ->where('status', '!=', 'cancelled')
                ->pluck('booking_time')
                ->map(fn($time) => Carbon::parse($time)->format('H:i'))
                ->toArray();

            $slots = [];
            $currentTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);

            while ($currentTime < $endTime) {
                $timeSlot = $currentTime->format('H:i');

                $slotDateTime = Carbon::parse($date . ' ' . $timeSlot);
                if ($slotDateTime->lte($now)) {
                    $currentTime->addMinutes(30);
                    continue;
                }

                $isBreakTime = false;
                if ($schedule->break_start && $schedule->break_end) {
                    $breakStart = Carbon::parse($schedule->break_start);
                    $breakEnd = Carbon::parse($schedule->break_end);
                    $isBreakTime = $currentTime->between($breakStart, $breakEnd);
                }

                if (!$isBreakTime && !in_array($timeSlot, $bookedTimes)) {
                    $slots[] = $timeSlot;
                }

                $currentTime->addMinutes(30);
            }

            return $slots;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function isAvailable($dateTime): bool
    {
        $date = Carbon::parse($dateTime)->toDateString();
        $time = Carbon::parse($dateTime)->format('H:i');

        $availableSlots = $this->getAvailableSlots($date);

        return in_array($time, $availableSlots);
    }

    public function getMonthAvailability($yearMonth): array
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $daysInMonth = $date->daysInMonth;

        $result = [
            'available_days' => [],
            'fully_booked_days' => [],
            'holiday_days' => []
        ];

        $hasSchedules = $this->schedules()->where('is_active', true)->exists();

        if (!$hasSchedules) {
            return $result;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $date->copy()->setDay($day);

            if ($this->holidays()->whereDate('date', $currentDate)->exists()) {
                $result['holiday_days'][] = $currentDate->format('Y-m-d');
                continue;
            }

            if ($this->leaves()
                ->whereDate('start_date', '<=', $currentDate)
                ->whereDate('end_date', '>=', $currentDate)
                ->where('status', 'approved')
                ->exists()
            ) {
                $result['holiday_days'][] = $currentDate->format('Y-m-d');
                continue;
            }

            $availableSlots = $this->getAvailableSlots($currentDate->format('Y-m-d'));
            if (empty($availableSlots)) {
                $result['fully_booked_days'][] = $currentDate->format('Y-m-d');
            } else {
                $result['available_days'][] = [
                    'date' => $currentDate->format('Y-m-d'),
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
}
