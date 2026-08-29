<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use App\Traits\HasJalaliDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, HasJalaliDates, SoftDeletes, BelongsToSalon;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'image',
        'category_id',
        'author_id',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Because the blog_posts table has a deleted_at (softDeletes) column, but the model didn't already
     * have the corresponding trait, every "delete" was effectively a permanent, irreversible delete.
     * With the addition of SoftDeletes, deleting a post is now truly a soft-delete.
     */
    protected $appends = [
        'image_url',
        'published_at_jalali',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post) {
            if (! $post->slug) {
                $post->slug = Str::slug($post->title);
            }

            if (! $post->author_id) {
                $post->author_id = auth()->id();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : null;
    }

    public function getPublishedAtJalaliAttribute(): ?string
    {
        return $this->published_at
            ? $this->toJalali($this->published_at, 'Y/m/d H:i')
            : null;
    }
}
