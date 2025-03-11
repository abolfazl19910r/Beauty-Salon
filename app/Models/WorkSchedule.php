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

    public static function updateOrCreate(array $array, array $validated)
    {
        return parent::updateOrCreate($array, $validated);
    }

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function isWorkingDay($date): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        return in_array($dayOfWeek, $this->work_days);
    }

    public function isWorkingTime($time): bool
    {
        $time = Carbon::parse($time)->format('H:i');
        return $time >= $this->start_time && $time <= $this->end_time;
    }

    public function getAvailableTimeSlots($date): array
    {
        if (!$this->isWorkingDay($date)) {
            return [];
        }

        $slots = [];
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

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
