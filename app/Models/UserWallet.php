<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_deposited',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(UserWalletTransaction::class, 'wallet_id');
    }

    public function addRefund(float $amount, int $bookingId, ?string $description = null): UserWalletTransaction
    {
        $this->increment('balance', $amount);

        $transaction = $this->transactions()->create([
            'booking_id' => $bookingId,
            'type' => 'refund',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "بازگشت وجه از نوبت #{$bookingId}",
        ]);

        return $transaction;
    }

    public function deductPayment(float $amount, int $bookingId, ?string $description = null): UserWalletTransaction
    {
        if ($amount > $this->balance) {
            throw new \Exception('موجودی کافی نیست');
        }

        $this->decrement('balance', $amount);
        $this->increment('total_spent', $amount);

        $transaction = $this->transactions()->create([
            'booking_id' => $bookingId,
            'type' => 'payment',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "پرداخت برای نوبت #{$bookingId}",
        ]);

        return $transaction;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance).' تومان';
    }
}
