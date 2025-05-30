<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'image',
        'category_id',
        'author_id',
        'is_published',
        'published_at'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime'
    ];

    /**
     * @throws Exception
     */
    public static function create(array $validated): static
    {
        $post = new static();
        $post->fill($validated);

        if (!$post->slug && isset($validated['title'])) {
            $post->slug = Str::slug($validated['title']);
        }

        if (!$post->author_id) {
            $post->author_id = auth()->id();
        }

        try {
            $saved = $post->save();
            return $post;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (!$post->slug) {
                $post->slug = Str::slug($post->title);
            }

            if (!$post->author_id) {
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
            ? asset('storage/' . $this->image)
            : null;
    }

    public function getPublishedAtJalaliAttribute(): ?string
    {
        return $this->published_at
            ? Jalalian::fromCarbon($this->published_at)->format('Y/m/d H:i')
            : null;
    }
}
