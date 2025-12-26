<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
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
        return $this->belongsTo(SpecialistWallet::class, 'wallet_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'income' => 'درآمد',
            'withdrawal' => 'برداشت',
            'cancellation_fee' => 'جریمه لغو',
            'refund' => 'بازگشت وجه',
            'adjustment' => 'تعدیل',
            default => 'نامشخص'
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            'income' => 'green',
            'withdrawal' => 'blue',
            'cancellation_fee' => 'red',
            'refund' => 'yellow',
            'adjustment' => 'gray',
            default => 'gray'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . number_format($this->amount) . ' تومان';
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }
}
