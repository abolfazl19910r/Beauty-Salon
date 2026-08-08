<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'specialist_id',
        'reference_code',
        'amount',
        'fee',
        'net_amount',
        'method',
        'iban',
        'account_holder_name',
        'status',
        'admin_note',
        'rejection_reason',
        'processed_at',
        'processed_by',
        'payment_details',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'payment_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->reference_code) {
                $model->reference_code = 'WD-'.strtoupper(Str::random(10));
            }
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SpecialistWallet::class, 'wallet_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار بررسی',
            'processing' => 'در حال پردازش',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => 'نامشخص'
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    public function getMethodTextAttribute(): string
    {
        return match ($this->method) {
            'instant' => 'فوری',
            'iban' => 'شبا',
            default => 'نامشخص'
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markAsCompleted(array $paymentDetails = []): bool
    {
        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'payment_details' => $paymentDetails,
        ]);
    }

    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'rejection_reason' => $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getFormattedIbanAttribute(): string
    {
        return 'IR'.chunk_split(substr($this->iban, 2), 4, ' ');
    }
}
