<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'work_days',
        'start_time',
        'end_time',
        'is_active'
    ];

    protected $casts = [
        'work_days' => 'array',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function isWorkingDay($date): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        return in_array($dayOfWeek, $this->work_days);
    }

    /**
     * Bugfix: Because start_time/end_time are stored with a cast of "datetime:H:i",
     * $this->start_time returns a Carbon object, not a string. Direct comparison
     * of $time (string "H:i") with a Carbon object with >=/<= operator does not behave correctly
     * (PHP converts the object to the full format "Y-m-d H:i:s" with __toString and
     * the result of the comparison is meaningless). Both sides must be explicitly formatted as "H:i".
 */
    public function isWorkingTime($time): bool
    {
        $time = Carbon::parse($time)->format('H:i');
        $start = $this->start_time?->format('H:i');
        $end = $this->end_time?->format('H:i');

        return $start !== null && $end !== null && $time >= $start && $time <= $end;
    }

    public function getAvailableTimeSlots($date): array
    {
        if (!$this->isWorkingDay($date)) {
            return [];
        }

        $slots = [];
        $start = Carbon::parse($this->start_time?->format('H:i'));
        $end = Carbon::parse($this->end_time?->format('H:i'));

        while ($start <= $end) {
            $slots[] = $start->format('H:i');
            $start->addMinutes(30);
        }

        return $slots;
    }

    public function isAvailable($dateTime): bool
    {
        $date = Carbon::parse($dateTime)->format('Y-m-d');
        $time = Carbon::parse($dateTime)->format('H:i');

        return $this->is_active &&
            $this->isWorkingDay($date) &&
            $this->isWorkingTime($time);
    }
}
