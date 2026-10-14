<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

/**
 * متن پیامک اختصاصی یک سالن برای یک نوع پیامک (تصمیم ۲۰۲۶-۱۰-۱۴؛ App\Support\Sms\SmsTemplates).
 */
class SalonSmsTemplate extends Model
{
    use BelongsToSalon;

    protected $fillable = ['salon_id', 'key', 'body'];
}
