<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'user_id',
        'specialist_id',
        'service_id',
        'overall_rating',
        'quality_rating',
        'behavior_rating',
        'cleanliness_rating',
        'speed_rating',
        'comment',
        'review_token',
        'reviewed_at',
        'is_approved',
        'is_featured',
        'specialist_response',
        'responded_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'responded_at' => 'datetime',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            if (! $review->review_token) {
                $review->review_token = Str::random(64);
            }
            if (! $review->reviewed_at) {
                $review->reviewed_at = now();
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query)
    {
        return $query->latest('reviewed_at');
    }

    public function scopeByRating($query, $minRating)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    public function scopeNegative($query)
    {
        return $query->where('overall_rating', '<', 3);
    }

    public function getAverageRating(): float
    {
        return round((
            $this->overall_rating +
            $this->quality_rating +
            $this->behavior_rating +
            $this->cleanliness_rating +
            $this->speed_rating
        ) / 5, 1);
    }

    public function isNegative(): bool
    {
        return $this->overall_rating < 3;
    }

    public function hasResponse(): bool
    {
        return ! empty($this->specialist_response);
    }

    public function canBeEdited(): bool
    {
        return $this->reviewed_at->diffInHours(now()) < 24;
    }

    public function getRatingTextAttribute(): string
    {
        return match ($this->overall_rating) {
            5 => 'عالی',
            4 => 'خوب',
            3 => 'متوسط',
            2 => 'ضعیف',
            1 => 'بسیار ضعیف',
            default => 'نامشخص'
        };
    }

    public function getRatingColorAttribute(): string
    {
        return match ($this->overall_rating) {
            5 => 'green',
            4 => 'blue',
            3 => 'yellow',
            2 => 'orange',
            1 => 'red',
            default => 'gray'
        };
    }
}
