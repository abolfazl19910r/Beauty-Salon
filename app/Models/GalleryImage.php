<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use BelongsToSalon, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'service_id',
        'specialist_id',
        'order',
        'is_active',
        'imageable_id',
        'imageable_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image_path);
    }

    /** خدمتِ این نمونه‌کار (اختیاری، بسته‌ی ۵ب). */
    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    /** کارشناسِ این نمونه‌کار (اختیاری، بسته‌ی ۵ب). */
    public function specialist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialist::class, 'specialist_id');
    }

    public function imageable()
    {
        return $this->morphTo();
    }
}
