<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loyalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_required',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'discount_percentage' => 'float',
        'is_active' => 'boolean',
    ];
}
