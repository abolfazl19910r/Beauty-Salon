<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\LoyaltyPoint;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'service_id',
        'specialist_id',
        'user_id',
        'booking_time',
        'status',
        'discount_code',
        'discount_amount',
        'prepayment_amount',
        'payment_status',
        'payment_reference',
        'payment_details',
        'paid_at',
        'rating',
        'review',
        'cancelled_by',
        'cancellation_reason',
        'cancelled_at',
        'reminder_sent',
        'refund_status',
        'refunded_at',
        'refunded_amount',
        'refund_reference',
        'refund_details'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function loyaltyPoints(): Booking|HasOne
    {
        return $this->hasOne(LoyaltyPoint::class);

        // یا اگر هر رزرو می‌تواند شامل چندین تراکنش امتیاز باشد:
        // return $this->hasMany(LoyaltyPoint::class);
    }
}
