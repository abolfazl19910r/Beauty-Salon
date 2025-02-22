<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeautyService extends Model
{
    protected $table = 'beauty_services';

    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration',
        'image',
        'category_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (!$service->slug) {
                $service->slug = Str::slug($service->name);
            }
        });

        static::deleting(function($service) {
            $service->specialists()->detach();

            $service->bookings()->delete();

            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
        });
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public static function latest()
    {
        return self::orderBy('created_at', 'desc');
    }

    public static function paginate(int $perPage = 15)
    {
        return self::latest()->paginate($perPage);
    }

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function specialists(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'specialist_services');
    }
}
