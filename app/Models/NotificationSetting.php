<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'label',
        'sms_enabled',
        'database_enabled',
        'telegram_enabled',
    ];

    protected $casts = [
        'sms_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
    ];
}
