<?php

namespace App\Models;

use App\Support\Iban;
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
        'needs_manual_check',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'needs_manual_check' => 'boolean',
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

    /**
     * لغو توسط متخصص. نتیجه‌ی نامعلوم تسویه (needs_manual_check) یعنی شاید پول واریز شده باشد؛ برگشت به کیف پول احتمال پرداخت
     * دوباره است، پس فقط مدیر بعد از دیدن پنل درگاه تأیید یا رد می‌کند.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']) && ! $this->needs_manual_check;
    }

    public function markAsCompleted(array $paymentDetails = []): bool
    {
        // ⭐ اگر نتیجه‌ی تسویه‌ی خودکار نامعلوم بود، سابقه‌اش (track_id، پیام درگاه) کنار تایید دستی می‌مونه
        if ($this->needs_manual_check) {
            $paymentDetails['unknown_payout'] = (array) $this->payment_details;
        }

        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'payment_details' => $paymentDetails,
            'needs_manual_check' => false,
        ]);
    }

    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'rejection_reason' => $reason,
            'needs_manual_check' => false,
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

    /**
     * تصمیم ۲۰۲۶-۰۹-۳۰: تسویه‌ی خودکار فقط به شبایی که مدیر تأیید کرده. شبای درخواست هنگام ثبت از کیف پول کپی
     * می‌شود؛ اگر متخصص بعداً شبا را عوض کرده باشد، تأیید فعلی کیف پول درباره‌ی شبای دیگری است.
     * null یعنی مانعی نیست؛ وگرنه دلیل فارسی برای نمایش به مدیر.
     */
    public function autoPayoutBlocker(): ?string
    {
        $wallet = $this->wallet;

        if (! $wallet || ! $wallet->iban) {
            return 'متخصص شماره شبا ثبت نکرده است؛ تسویه‌ی خودکار ممکن نیست.';
        }

        if (! Iban::isValid($this->iban)) {
            return 'شبای این درخواست معتبر نیست (رقم کنترلی نمی‌خواند)؛ تسویه‌ی خودکار ممکن نیست.';
        }

        if (Iban::normalize((string) $this->iban) !== Iban::normalize((string) $wallet->iban)) {
            return 'شبای این درخواست با شبای فعلی کیف پول متخصص یکی نیست (متخصص بعد از ثبت درخواست شبا را عوض کرده). درخواست را دستی تسویه یا رد کنید.';
        }

        if (! $wallet->iban_verified) {
            return 'شبای متخصص هنوز تأیید نشده است. تسویه‌ی خودکار فقط به شبای تأییدشده انجام می‌شود؛ ابتدا شبا را در صفحه‌ی کیف پول متخصص تأیید کنید یا مبلغ را دستی واریز کنید.';
        }

        return null;
    }
}
