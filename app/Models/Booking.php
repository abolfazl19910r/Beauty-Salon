<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Booking extends Model
{
    use BelongsToSalon, HasFactory;

    protected $table = 'bookings';

    protected $casts = [
        'booking_time' => 'datetime',
        'payment_details' => 'array',
        'refund_details' => 'array',
        'reminder_sent' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected $fillable = [
        'service_id',
        'specialist_id',
        'user_id',
        'booking_time',
        'status',
        'source',
        'discount_code',
        'discount_amount',
        'prepayment_amount',
        'payment_status',
        'payment_reference',
        'payment_details',
        'paid_at',
        'rating',
        'review',
        'notes',
        'cancelled_by',
        'cancellation_reason',
        'cancelled_at',
        'reminder_sent',
        'reviewed_at',
        'refund_status',
        'refunded_at',
        'refunded_amount',
        'refund_reference',
        'refund_details',
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

    /**
     * The amount the customer still owes the specialist directly at the appointment
     * (service price minus the prepayment collected through the platform). Display-only —
     * never used in any wallet/commission calculation (BookingObserver::addIncomeAndCommission()
     * only ever operates on prepayment_amount, the actual money collected through the platform).
     */
    /** ⭐ تراکنش‌های درگاه این نوبت (دفتر واحد payment_transactions). */
    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }

    /**
     * ⭐ نوبت‌هایی که پرداختشون در جریان نیست — لغو خودکار «پرداخت‌نشده بعد از ۳۰ دقیقه» فقط روی این‌ها. مهلت ۳۰
     * دقیقه از ساخت نوبت شمرده می‌شه نه از رفتن به بانک؛ بدون این، مشتری‌ای که در دقیقه‌ی ۲۸ پرداخت می‌کرد و در
     * ۳۲ برمی‌گشت نوبتش وسط پرداخت لغو می‌شد (و اگه ساعتش رو کس دیگه‌ای می‌گرفت، پولش گیر می‌کرد).
     */
    public function scopeWithoutPaymentInProgress($query)
    {
        return $query->whereDoesntHave('paymentTransactions', fn ($q) => $q
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(PaymentTransaction::PENDING_LIFETIME_MINUTES)));
    }

    public function getRemainingAmountAttribute(): float
    {
        if (! $this->service) {
            return 0.0;
        }

        $remaining = (float) $this->service->price - (float) $this->prepayment_amount - (float) $this->discount_amount;

        return max(0.0, $remaining);
    }

    public function loyaltyPoints(): Booking|HasOne
    {
        return $this->hasOne(LoyaltyPoint::class);

        // or if each reservation can contain multiple points transactions:
        // return $this->hasMany(LoyaltyPoint::class);
    }

    public function canBeRescheduled(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        // The appointment must be at least 24 hours away.
        return \Carbon\Carbon::parse($this->booking_time)
            ->gt(now()->addHours(24));
    }
}
