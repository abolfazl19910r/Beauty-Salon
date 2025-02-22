<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_id',
        'specialist_id',
        'user_id',
        'booking_time',
        'status',
        'prepayment_amount',
        'payment_status',
        'payment_ref',
        'payment_details',
        'paid_at',
        'discount_code',
        'discount_amount',
        'rating',
        'review',
        'reminder_sent',
        'refund_status',
        'refunded_at',
        'refunded_amount',
        'refund_reference',
        'refund_details'
    ];

    protected $casts = [
        'booking_time' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'payment_details' => 'json',
        'refund_details' => 'json',
        'reminder_sent' => 'boolean'
    ];

    protected array $dates = ['booking_time'];

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BeautyService::class);
    }

    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canBeRescheduled(): bool
    {
        return $this->status !== 'cancelled' &&
            $this->booking_time->diffInHours(now()) > 24;
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== 'cancelled' &&
            $this->booking_time->diffInHours(now()) > 24;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDue(): bool
    {
        return $this->booking_time->isPast() && !$this->rating;
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'در انتظار تایید',
            'confirmed' => 'تایید شده',
            'cancelled' => 'لغو شده',
            default => 'نامشخص'
        };
    }

    public function getRemainingTimeAttribute(): string
    {
        if ($this->isPending()) {
            return $this->booking_time->longAbsoluteDiffForHumans();
        }
        return '';
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_time', '>', now())
            ->whereNotIn('status', ['cancelled']);
    }

    public function scopePast($query)
    {
        return $query->where('booking_time', '<=', now());
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isRefundable(): bool
    {
        return $this->payment_status === 'paid' &&
            $this->status === 'cancelled' &&
            !$this->isRefunded() &&
            $this->booking_time->isFuture();
    }

    public function isRefunded(): bool
    {
        return $this->refund_status === 'refunded';
    }

    public function hasRefundFailed(): bool
    {
        return $this->refund_status === 'failed';
    }

    public function isPendingRefund(): bool
    {
        return $this->refund_status === 'pending';
    }

    public function markAsRefunded(array $details = []): bool
    {
        return $this->update([
            'refund_status' => 'refunded',
            'refunded_at' => now(),
            'refunded_amount' => $this->prepayment_amount,
            'refund_details' => array_merge(
                $details,
                ['refunded_by' => auth()->id() ?? 'system']
            )
        ]);
    }

    public function markAsRefundFailed(array $details = []): bool
    {
        return $this->update([
            'refund_status' => 'failed',
            'refund_details' => array_merge(
                $details,
                ['failed_at' => now()->toDateTimeString()]
            )
        ]);
    }

    public function getRefundableAmount(): float
    {
        if ($this->booking_time->diffInHours(now()) < 24) {
            return $this->prepayment_amount * 0.8;
        }

        return $this->prepayment_amount;
    }

    public function getRefundStatusTextAttribute(): string
    {
        return match($this->refund_status) {
            'pending' => 'در انتظار بررسی',
            'refunded' => 'برگشت داده شده',
            'failed' => 'ناموفق',
            default => 'نامشخص'
        };
    }

    public function getRefundStatusColorAttribute(): string
    {
        return match($this->refund_status) {
            'pending' => 'yellow',
            'refunded' => 'green',
            'failed' => 'red',
            default => 'gray'
        };
    }
}
