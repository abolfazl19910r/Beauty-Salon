<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', Carbon::parse($date));
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [
            Carbon::parse($startDate),
            Carbon::parse($endDate),
        ]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::today())
            ->orderBy('date');
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', Carbon::today())
            ->orderBy('date', 'desc');
    }

    public function isPastHoliday(): bool
    {
        return $this->date < Carbon::today();
    }
}
