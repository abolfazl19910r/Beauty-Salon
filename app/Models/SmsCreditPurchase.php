<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * خرید آنلاین بسته‌ی پیامک یا اعطای دستی سوپرادمین (۲۰۲۶-۰۹-۳۰). parts بر حسب قطعه، amount به تومان.
 * عمداً BelongsToSalon ندارد: صفحه‌ی صورتحساب و سوپرادمین صریحاً با salon_id فیلتر می‌کنند.
 */
class SmsCreditPurchase extends Model
{
    protected $fillable = ['salon_id', 'parts', 'amount', 'source', 'status', 'authority', 'ref_id', 'note', 'created_by', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime', 'parts' => 'integer', 'amount' => 'integer'];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
