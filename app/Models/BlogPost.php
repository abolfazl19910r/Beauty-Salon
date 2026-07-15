<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

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
     * چون جدول blog_posts ستون deleted_at (softDeletes) دارد ولی مدل قبلاً
     * trait مربوطه را نداشت، هر «حذف» عملاً حذف دائمی و غیرقابل‌بازگشت بود.
     * با اضافه شدن SoftDeletes، حذف مقاله از این پس واقعاً soft-delete است.
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
            ? Jalalian::fromCarbon($this->published_at)->format('Y/m/d H:i')
            : null;
    }
}
