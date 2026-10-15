<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ارسال یک پیام اپ به یک دستگاه و رسید همان دستگاه (بسته‌ی ۴). */
class PushDelivery extends Model
{
    public const QUEUED = 'queued';

    public const ACCEPTED = 'accepted';

    public const FAILED = 'failed';

    public const INVALID = 'invalid';

    protected $fillable = ['push_message_id', 'push_device_id', 'status', 'attempts', 'error', 'accepted_at', 'acked_at', 'opened_at'];

    protected $casts = [
        'accepted_at' => 'datetime',
        'acked_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(PushMessage::class, 'push_message_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(PushDevice::class, 'push_device_id');
    }
}
