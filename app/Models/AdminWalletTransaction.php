<?php

namespace App\Models;

use App\Models\AdminWallet;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_wallet_id',
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

    public function adminWallet()
    {
        return $this->belongsTo(AdminWallet::class, 'admin_wallet_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'commission' => 'کمیسیون',
            'withdrawal' => 'برداشت',
            'adjustment' => 'تعدیل',
            default => 'نامشخص'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . number_format($this->amount) . ' تومان';
    }
}
