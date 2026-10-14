<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک ردیف تنظیم سطح پلتفرم (بدون salon_id؛ TenantModelClassificationTest). خواندن/نوشتن از App\Support\PlatformSettings.
 */
class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
