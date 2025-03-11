<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active'
    ];

    protected array $dates = ['expires_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'used_count' => 'integer',
        'max_uses' => 'integer'
    ];

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'discount_code', 'code');
    }

    public function isValid(): bool
    {
        return $this->is_active &&
            $this->used_count < $this->max_uses &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function calculateDiscount($amount)
    {
        if ($this->type === 'percentage') {
            return ($amount * $this->amount) / 100;
        }
        return $this->amount;
    }

    public function incrementUsage()
    {
        $this->increment('used_count');
    }
}
