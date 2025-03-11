<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'reference_id',
        'card_data',
        'status',
        'gateway_reference',
        'gateway_response',
        'payment_details',
        'paid_at',
        'expired_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'json',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime'
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->expired_at && now()->isAfter($this->expired_at);
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);
    }

    public function markAsFailed(): bool
    {
        return $this->update([
            'status' => 'failed'
        ]);
    }

    public function setGatewayResponse(string $reference, array $response): bool
    {
        return $this->update([
            'gateway_reference' => $reference,
            'gateway_response' => json_encode($response)
        ]);
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount) . ' تومان';
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->expired_at) {
            return null;
        }

        $remaining = $this->expired_at->diffInSeconds(now(), false);
        return $remaining > 0 ? $remaining : 0;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expired_at', '<', now());
    }
}
