<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'booking_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'deposit' => 'واریز',
            'payment' => 'پرداخت',
            'refund' => 'بازگشت وجه',
            'adjustment' => 'تعدیل',
            default => 'نامشخص'
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            'deposit' => 'green',
            'refund' => 'blue',
            'payment' => 'red',
            'adjustment' => 'gray',
            default => 'gray'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';

        return $prefix.number_format($this->amount).' تومان';
    }
}
