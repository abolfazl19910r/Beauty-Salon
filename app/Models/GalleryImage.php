<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use HasFactory, BelongsToSalon;

    protected $fillable = [
        'title',
        'description',
        'image_path',
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

    public function imageable()
    {
        return $this->morphTo();
    }
}
