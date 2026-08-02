<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialistWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialist_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'pending_amount',
        'iban',
        'account_holder_name',
        'bank_name',
        'iban_verified',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'iban_verified' => 'boolean',
    ];

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'wallet_id');
    }

    public function addIncome(float $amount, int $bookingId, string $description = null): WalletTransaction
    {
        $settings = WalletSetting::first();
        $settlementDelay = $settings->settlement_delay_days ?? 2;

        $this->increment('pending_amount', $amount);
        $this->increment('total_earned', $amount);

        $transaction = $this->transactions()->create([
            'booking_id' => $bookingId,
            'type' => 'income',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "درآمد از نوبت #{$bookingId}",
            'metadata' => [
                'settlement_date' => now()->addDays($settlementDelay)->toDateString(),
                'status' => 'pending'
            ]
        ]);

        return $transaction;
    }

    public function settlePendingAmount(float $amount): void
    {
        $this->decrement('pending_amount', $amount);
        $this->increment('balance', $amount);
    }

    /**
     * R-Observers addendum: previously cancelling a paid booking refunded the customer but never
     * clawed back the specialist's income that was credited at payment time (addIncome() above),
     * meaning the salon effectively paid out twice per cancelled-after-paid booking. This reverses
     * that original credit — from pending_amount if it hadn't settled yet, or from balance if it
     * had (balance can legitimately go negative here if the specialist already withdrew it; the
     * caller logs this case so an admin can follow up).
     *
     * Uses the 'adjustment' enum value (not a new one) since wallet_transactions.type is a fixed
     * DB enum — adding a dedicated type would need a migration for something this narrow.
     */
    public function reverseIncome(WalletTransaction $originalTransaction, string $description = null): WalletTransaction
    {
        $amount = (float) $originalTransaction->amount;
        $wasSettled = ($originalTransaction->metadata['status'] ?? null) === 'settled';

        if ($wasSettled) {
            $this->decrement('balance', $amount);
        } else {
            $this->decrement('pending_amount', $amount);

            /**
             * R-Observers addendum (critical fix): without this, a still-pending income
             * transaction reversed here would keep metadata.status = 'pending' — meaning the
             * scheduled wallet:settle-pending command would later find it, see its
             * settlement_date has passed, and "settle" it AGAIN (moving the same amount from
             * pending_amount to balance a second time), crediting the specialist for a booking
             * that was already cancelled and refunded. Marking it 'reversed' here excludes it
             * from settlePendingIncomes()'s `metadata->status = 'pending'` query permanently.
             */
            $metadata = $originalTransaction->metadata ?? [];
            $metadata['status'] = 'reversed';
            $metadata['reversed_at'] = now()->toDateTimeString();
            $originalTransaction->update(['metadata' => $metadata]);
        }
        $this->decrement('total_earned', $amount);

        return $this->transactions()->create([
            'booking_id' => $originalTransaction->booking_id,
            'type' => 'adjustment',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description ?? "برگشت سهم به‌خاطر لغو نوبت #{$originalTransaction->booking_id}",
        ]);
    }

    public function recordWithdrawal(float $amount, int $withdrawalRequestId): WalletTransaction
    {
        $this->decrement('balance', $amount);
        $this->increment('total_withdrawn', $amount);

        $transaction = $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => "برداشت وجه - کد پیگیری: {$withdrawalRequestId}",
            'metadata' => [
                'withdrawal_request_id' => $withdrawalRequestId
            ]
        ]);

        return $transaction;
    }

    public function canWithdraw(float $amount): array
    {
        $settings = WalletSetting::first();

        if ($amount < $settings->minimum_withdrawal_amount) {
            return [
                'success' => false,
                'message' => "حداقل مبلغ برداشت " . number_format($settings->minimum_withdrawal_amount) . " تومان است."
            ];
        }

        if ($amount > $settings->maximum_withdrawal_amount) {
            return [
                'success' => false,
                'message' => "حداکثر مبلغ برداشت " . number_format($settings->maximum_withdrawal_amount) . " تومان است."
            ];
        }

        if ($amount > $this->balance) {
            return [
                'success' => false,
                'message' => "موجودی کیف پول شما کافی نیست."
            ];
        }

        if (!$this->iban) {
            return [
                'success' => false,
                'message' => "لطفاً ابتدا شماره شبا خود را ثبت کنید."
            ];
        }

        return ['success' => true];
    }

    public function calculateWithdrawalFee(float $amount, string $method = 'iban'): array
    {
        $settings = WalletSetting::first();

        $fee = 0;
        if ($method === 'instant' && $settings->instant_withdrawal_enabled) {
            $fee = $settings->instant_withdrawal_fee;
        } else {
            $fee = ($amount * $settings->withdrawal_fee_percentage) / 100;
        }

        $netAmount = $amount - $fee;

        return [
            'gross_amount' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount,
        ];
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance) . ' تومان';
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance;
    }

    public function setIbanAttribute($value)
    {
        $this->attributes['iban'] = str_replace(' ', '', strtoupper($value));
    }

    public function getFormattedIbanAttribute()
    {
        if (!$this->iban) return null;
        return implode(' ', str_split($this->iban, 4));
    }
}
