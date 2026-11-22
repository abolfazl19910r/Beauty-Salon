<?php

namespace App\Models;

use App\Support\PayoutDestination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * درخواست سالن برای پرداخت با درگاه پلتفرم (۲۰۲۶-۱۱-۲۲). سطح پلتفرم (BelongsToSalon ندارد)؛ مالک فقط از رابطه‌ی سالن جاری خودش.
 * هر سالن در هر لحظه حداکثر یک ردیف pending یا approved دارد (PlatformGatewayService).
 */
class PlatformGatewayRequest extends Model
{
    public const STATUSES = ['pending' => 'در انتظار بررسی', 'approved' => 'تأییدشده', 'rejected' => 'ردشده', 'revoked' => 'لغوشده'];

    protected $fillable = [
        'salon_id', 'requested_by', 'status', 'payout_method', 'payout_iban', 'payout_card', 'payout_account', 'payout_bank',
        'account_holder', 'owner_note', 'fee_percent', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $hidden = ['payout_iban', 'payout_card', 'payout_account'];

    protected $casts = [
        'payout_iban' => 'encrypted',
        'payout_card' => 'encrypted',
        'payout_account' => 'encrypted',
        'fee_percent' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class)->withoutGlobalScopes();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /** شماره‌ی مقصد واریز بر اساس روش انتخابی (کامل — فقط ابرمدیر) */
    public function destination(): ?string
    {
        return match ($this->payout_method) {
            'iban' => $this->payout_iban,
            'card' => $this->payout_card,
            'account' => $this->payout_account,
            default => null,
        };
    }

    public function maskedDestination(): string
    {
        return PayoutDestination::label($this->payout_method).' '.PayoutDestination::mask($this->destination());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
