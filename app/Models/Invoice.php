<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». به بخش InvoiceService نگاه کن — این مدل
 * صرفاً یک رکورد ساده است، منطق واقعی (ساخت فاکتور pending، علامت‌زدن paid/failed، تمدید دستی)
 * همیشه از طریق آن سرویس انجام می‌شود، نه مستقیماً روی این مدل.
 *
 * BelongsToSalon: پنل ادمین هر سالن فقط فاکتورهای خودش را می‌بیند (global scope خودکار)؛ پنل
 * سوپر ادمین (بدون CurrentSalon) همه‌ی فاکتورهای همه‌ی سالن‌ها را می‌بیند.
 */
class Invoice extends Model
{
    use BelongsToSalon, HasFactory;

    protected $fillable = [
        'salon_id',
        'subscription_type',
        'plan_id',
        'kind',
        'plan_snapshot',
        'amount',
        'status',
        'payment_method',
        'authority',
        'ref_id',
        'period_start',
        'period_end',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'plan_snapshot' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** نام پلن در لحظه‌ی خرید (snapshot)، نه نام امروز پلن */
    public function planName(): ?string
    {
        return $this->plan_snapshot['name'] ?? null;
    }

    public function isUpgrade(): bool
    {
        return $this->kind === 'upgrade';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
