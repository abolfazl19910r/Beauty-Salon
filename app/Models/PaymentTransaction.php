<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * ⭐ یک تراکنش درگاه (لایه‌ی چند درگاه — مرحله‌ی ۰ بخش ۲، ۲۰۲۶-۰۹-۲۵). عمداً BelongsToSalon نداره:
 * از مسیر عمومی /payments/return/{public_id} (بدون session و بدون CurrentSalon) خونده می‌شه.
 */
class PaymentTransaction extends Model
{
    public const PURPOSES = ['booking', 'wallet_charge', 'subscription'];

    /**
     * ⭐ وضعیت‌ها: pending (منتظر بازگشت مشتری) · paid · failed · cancelled · expired (مشتری برنگشت —
     * payments:reconcile) · reversing / reversed (کل مبلغ به کارت برگشت زده شد: پاسخ verify سامان نرسید، یا ساعت
     * نوبت هنگام بازگشت دیگه آزاد نبود) · refunded (پول گرفته شد ولی نوبت ثبت‌شدنی نبود → به کیف پول مشتری).
     * expired مانع تایید بعدی نیست؛ بقیه‌ی این سه وضعیت پایانی هستن و هرگز دوباره تایید/بازنویسی نمی‌شن.
     */
    public const REVERSAL_STATUSES = ['reversing', 'reversed', 'refunded'];

    /**
     * عمر یک تراکنش باز: تا این مدت مشتری ممکنه هنوز در صفحه‌ی بانک باشه — لغو خودکار نوبت‌های پرداخت‌نشده
     * (CancelUnpaidBookings / bookings:cleanup) از نوبتی با تراکنش باز جوان‌تر از این رد می‌شه، و بعدش
     * payments:reconcile تراکنش رو expired می‌کنه.
     */
    public const PENDING_LIFETIME_MINUTES = 60;

    protected $fillable = [
        'public_id', 'salon_id', 'gateway_id', 'driver', 'purpose', 'payable_type', 'payable_id', 'user_id',
        'amount_rial', 'fee_rial', 'token', 'ref_id', 'gateway_receipt', 'card_pan', 'status', 'callback_url',
        'start_response', 'verify_response', 'verified_at',
    ];

    protected $casts = [
        'amount_rial' => 'integer',
        'fee_rial' => 'integer',
        'start_response' => 'array',
        'verify_response' => 'array',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentTransaction $tx) {
            $tx->public_id ??= (string) Str::uuid();
        });
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SalonPaymentGateway::class, 'gateway_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
