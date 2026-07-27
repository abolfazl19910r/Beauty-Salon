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

    /**
     * R-Observers addendum: reverses the platform's commission cut for a booking that was paid
     * and later cancelled — see SpecialistWallet::reverseIncome() for the matching specialist-side
     * reversal and the full reasoning. Uses 'adjustment' (existing enum value) rather than a new
     * commission_reversal type, since admin_wallet_transactions.type is a fixed DB enum.
     */
    public function deductCommission(float $amount, int $bookingId, string $description = null): AdminWalletTransaction
    {
        $this->decrement('balance', $amount);
        $this->decrement('total_earned', $amount);

        return $this->transactions()->create([
            'booking_id' => $bookingId,
            'type' => 'adjustment',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "برگشت کمیسیون به‌خاطر لغو نوبت #{$bookingId}",
        ]);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance) . ' تومان';
    }
}
