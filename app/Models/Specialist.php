<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Specialist extends Model
{
    use Notifiable;
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email'];

    public static function latest()
    {
        return self::orderBy('created_at', 'desc');
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SpecialistSchedule::class);
    }

    public function workSchedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WorkSchedule::class);
    }

    public function leaves(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SpecialistLeave::class);
    }

    public function holidays(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'specialist_id');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(BeautyService::class, 'specialist_services');
    }

    public function getAvailableSlots($date): array
    {
        $carbonDate = Carbon::parse($date);

        $hasLeave = $this->leaves()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', 'approved')
            ->exists();

        if ($hasLeave) {
            return [];
        }

        $schedule = $this->schedules()
            ->where('day_of_week', $carbonDate->dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [];
        }

        $slots = [];
        $currentTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        while ($currentTime < $endTime) {
            $timeSlot = $currentTime->format('H:i');

            $isBreakTime = false;
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = Carbon::parse($schedule->break_start);
                $breakEnd = Carbon::parse($schedule->break_end);
                $isBreakTime = $currentTime->between($breakStart, $breakEnd);
            }

            if (!$isBreakTime) {
                $isBooked = $this->bookings()
                    ->whereDate('booking_time', $date)
                    ->whereTime('booking_time', $timeSlot)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$isBooked) {
                    $slots[] = $timeSlot;
                }
            }

            $currentTime->addMinutes(30);
        }

        return $slots;
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
}
