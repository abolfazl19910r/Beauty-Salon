<?php

namespace App\Models;

use App\Support\PayoutDestination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * یک دسته تسویه‌ی درگاه پلتفرم با یک سالن (۲۰۲۶-۱۱-۲۲): همه‌ی تراکنش‌های تسویه‌نشده‌ی آن سالن تا لحظه‌ی ساخت. جمع‌ها هنگام ساخت
 * ثابت می‌شوند؛ واریز بانکی بیرون از سیستم انجام و با «شماره‌ی پیگیری بانک» ثبت می‌شود (زرین‌پال API واریز به شخص ثالث ندارد).
 */
class PlatformSettlement extends Model
{
    public const STATUSES = ['pending' => 'منتظر واریز', 'paid' => 'واریز شد', 'cancelled' => 'لغو شد'];

    protected $fillable = [
        'salon_id', 'status', 'transactions_count', 'gross_rial', 'platform_share_rial', 'salon_share_rial', 'period_from', 'period_to',
        'payout_method', 'payout_destination', 'account_holder', 'automatic', 'bank_reference', 'paid_at', 'created_by', 'paid_by', 'note',
    ];

    protected $hidden = ['payout_destination'];

    protected $casts = [
        'transactions_count' => 'integer',
        'gross_rial' => 'integer',
        'platform_share_rial' => 'integer',
        'salon_share_rial' => 'integer',
        'period_from' => 'datetime',
        'period_to' => 'datetime',
        'payout_destination' => 'encrypted',
        'automatic' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class)->withoutGlobalScopes();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'platform_settlement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function maskedDestination(): string
    {
        return PayoutDestination::label($this->payout_method).' '.PayoutDestination::mask($this->payout_destination);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
