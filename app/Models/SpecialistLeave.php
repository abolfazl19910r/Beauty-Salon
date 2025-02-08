<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialistLeave extends Model
{
    protected $fillable = [
        'specialist_id',
        'start_date',
        'end_date',
        'reason',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }
}
