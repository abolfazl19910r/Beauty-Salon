<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialistSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active'
    ];

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }
}
