<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletSetting extends Model
{
    use HasFactory, BelongsToSalon;

    protected $fillable = [
        'withdrawal_fee_percentage',
        'minimum_withdrawal_amount',
        'maximum_withdrawal_amount',
        'instant_withdrawal_enabled',
        'instant_withdrawal_fee',
        'cancellation_before_hours',
        'customer_cancellation_fee_percentage',
        'specialist_cancellation_penalty_percentage',
        'specialist_cancellation_before_hours',
        'specialist_repeat_cancellation_threshold',
        'specialist_repeat_cancellation_window_days',
        'specialist_repeat_cancellation_extra_percentage',
        'settlement_delay_days',
        'admin_commission_percentage',
        'prepayment_percentage',
        'minimum_prepayment_amount',
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
        'specialist_cancellation_before_hours' => 'integer',
        'specialist_repeat_cancellation_threshold' => 'integer',
        'specialist_repeat_cancellation_window_days' => 'integer',
        'specialist_repeat_cancellation_extra_percentage' => 'decimal:2',
        'settlement_delay_days' => 'integer',
        'admin_commission_percentage' => 'decimal:2',
        'prepayment_percentage' => 'decimal:2',
        'minimum_prepayment_amount' => 'decimal:2',
    ];

    public function calculateCustomerCancellationFee(float $amount, $bookingTime): float
    {
        $hoursUntilBooking = now()->diffInHours($bookingTime, false);

        if ($hoursUntilBooking > $this->cancellation_before_hours) {
            return 0;
        }

        return ($amount * $this->customer_cancellation_fee_percentage) / 100;
    }

    /**
     * ⭐ Update: Previously it was unlimited (always penalized, even if the specialist
     * canceled far ahead of schedule) — now it has a separate time threshold
     * (`specialist_cancellation_before_hours`) like the client. Also an increased penalty for
     * repeated cancellations: If `$recentCancellationsCount` (the number of recent cancellations by the specialist within
     * `specialist_repeat_cancellation_window_days`) reaches or exceeds the
     * `specialist_repeat_cancellation_threshold`,
     * `specialist_repeat_cancellation_extra_percentage` is added to the base percentage.
     * `threshold = 0` means this feature is disabled (default).
     */
    public function calculateSpecialistCancellationPenalty(float $amount, $bookingTime, int $recentCancellationsCount = 0): float
    {
        $hoursUntilBooking = now()->diffInHours($bookingTime, false);

        if ($hoursUntilBooking > $this->specialist_cancellation_before_hours) {
            return 0;
        }

        $percentage = (float) $this->specialist_cancellation_penalty_percentage;

        if ($this->specialist_repeat_cancellation_threshold > 0
            && $recentCancellationsCount >= $this->specialist_repeat_cancellation_threshold) {
            $percentage += (float) $this->specialist_repeat_cancellation_extra_percentage;
        }

        $percentage = min($percentage, 100);

        return ($amount * $percentage) / 100;
    }

    /**
     * Admin-configurable prepayment: percentage of the service price, with a floor
     * (minimum_prepayment_amount) and a ceiling (never more than the service's own total price —
     * fixes a real edge case where a cheap service's price could otherwise be less than the
     * configured minimum, which would make the customer's "prepayment" exceed the actual cost of
     * the service).
     */
    public function calculatePrepaymentAmount(float $servicePrice): float
    {
        $percentageBased = $servicePrice * ((float) $this->prepayment_percentage / 100);
        $amount = max((float) $this->minimum_prepayment_amount, $percentageBased);

        return min($servicePrice, $amount);
    }

    public static function get(): self
    {
        return self::first() ?? self::create([]);
    }
}
