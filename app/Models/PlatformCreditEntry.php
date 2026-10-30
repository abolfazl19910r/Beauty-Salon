<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ⭐ دفتر «اعتبار پلتفرم» سالن (۲۰۲۶-۱۰-۳۱) — App\Services\Payment\PlatformCredit. مثبت: قسط‌های فاکتور منقضی، قسط دیرهنگام، اعتبار دستی؛
 * منفی: رزرو برای فاکتور تازه. مثل SalonPaymentGateway عمداً BelongsToSalon ندارد: همیشه با salon_id صریح.
 */
class PlatformCreditEntry extends Model
{
    protected $fillable = ['salon_id', 'amount', 'reason', 'invoice_id', 'payment_transaction_id', 'note'];

    protected $casts = ['amount' => 'integer'];
}
