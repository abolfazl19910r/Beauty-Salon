<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

/**
 * شمار روزانه‌ی بازدید از QR سالن (specialist_id = 0) یا QR یک متخصص (بسته‌ی ۵). نوشتن با App\Services\Qr\QrScanCounter.
 */
class QrScan extends Model
{
    use BelongsToSalon;

    public $timestamps = false;

    protected $fillable = ['salon_id', 'specialist_id', 'scan_date', 'hits'];

    protected $casts = ['scan_date' => 'date', 'hits' => 'integer', 'specialist_id' => 'integer'];
}
