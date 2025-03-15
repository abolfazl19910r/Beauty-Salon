<?php

namespace App\Models;

use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            $activeBookings = $service->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->orWhere('payment_status', 'paid')
                ->count();

            if ($activeBookings > 0) {
                throw new \Exception('این سرویس دارای رزروهای فعال است و نمی‌توان آن را حذف کرد.');
            }

            $service->specialists()->detach();

            $service->bookings()->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'سرویس حذف شده است'
            ]);

            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'specialist_services');
    }
}
