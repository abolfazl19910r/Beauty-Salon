<?php

namespace App\Models;

use App\Models\AdminWalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminWallet extends Model
{
    use HasFactory;

    protected $table = 'admin_wallet';

    protected $fillable = [
        'balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AdminWalletTransaction::class, 'admin_wallet_id');
    }

    public static function getWallet(): self
    {
        return self::first() ?? self::create([
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);
    }

    public function addCommission(float $amount, int $bookingId, string $description = null): AdminWalletTransaction
    {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);

        $transaction = $this->transactions()->create([
            'booking_id' => $bookingId,
            'type' => 'commission',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "کمیسیون از نوبت #{$bookingId}",
        ]);

        return $transaction;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance) . ' تومان';
    }
}

