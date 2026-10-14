<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** یک گیرنده‌ی کمپین دستی (بسته‌ی ج): ثبت لحظه‌ی شروع، هر کدام حداکثر یک پیامک */
class WinbackCampaignRecipient extends Model
{
    use BelongsToSalon;

    public const SKIP_REASONS = [
        'opted_out' => 'انصراف از پیامک‌ها',
        'booked' => 'نوبت آینده دارد',
        'no_phone' => 'شماره ندارد',
        'recently_texted' => 'در ۹۰ روز اخیر پیامک پیگیری گرفته',
        'cancelled' => 'کمپین لغو شد',
    ];

    protected $fillable = [
        'salon_id', 'winback_campaign_id', 'user_id', 'last_visit_at', 'status', 'skip_reason', 'discount_code_id', 'sms_parts', 'sent_at',
    ];

    protected $casts = ['last_visit_at' => 'datetime', 'sent_at' => 'datetime', 'sms_parts' => 'integer'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WinbackCampaign::class, 'winback_campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }
}
