<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** هر پیامک «پیگیری مشتری غایب» که واقعاً فرستاده شد؛ last_visit_at همان آخرین مراجعه‌ای است که این دوره‌ی غیبت را ساخت. */
class WinbackMessage extends Model
{
    use BelongsToSalon;

    protected $fillable = ['salon_id', 'user_id', 'last_visit_at', 'discount_code_id', 'sent_at'];

    protected $casts = [
        'last_visit_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }
}
