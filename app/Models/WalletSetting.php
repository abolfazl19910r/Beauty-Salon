<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_fee_percentage',
        'minimum_withdrawal_amount',
        'maximum_withdrawal_amount',
        'instant_withdrawal_enabled',
        'instant_withdrawal_fee',
        'cancellation_before_hours',
        'customer_cancellation_fee_percentage',
        'specialist_cancellation_penalty_percentage',
        'settlement_delay_days',
        'admin_commission_percentage',
    ];

    protected $casts = [
        'withdrawal_fee_percentage' => 'decimal:2',
        'minimum_withdrawal_amount' => 'decimal:2',
        'maximum_withdrawal_amount' => 'decimal:2',
        'instant_withdrawal_enabled' => 'boolean',
        'instant_withdrawal_fee' => 'decimal:2',
        'customer_cancellation_fee_percentage' => 'decimal:2',
        'specialist_cancellation_penalty_percentage' => 'decimal:2',
        'cancellation_before_hours' => 'integer',
        'settlement_delay_days' => 'integer',
        'admin_commission_percentage' => 'decimal:2',
    ];

    public function calculateCustomerCancellationFee(float $amount, $bookingTime): float
    {
        $hoursUntilBooking = now()->diffInHours($bookingTime, false);

        if ($hoursUntilBooking > $this->cancellation_before_hours) {
            return 0;
        }

        return ($amount * $this->customer_cancellation_fee_percentage) / 100;
    }

    public function calculateSpecialistCancellationPenalty(float $amount): float
    {
        return ($amount * $this->specialist_cancellation_penalty_percentage) / 100;
    }

    public static function get(): self
    {
        return self::first() ?? self::create([]);
    }
}
