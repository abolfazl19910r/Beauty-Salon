<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_active',
        'published_at',
        'expires_at',
        'priority',
        'type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'priority' => 'integer'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) return 'غیرفعال';
        if ($this->published_at > now()) return 'در انتظار انتشار';
        if ($this->expires_at && $this->expires_at < now()) return 'منقضی شده';
        return 'فعال';
    }

    public static function getActiveAnnouncements()
    {
        return static::query()
            ->active()
            ->byPriority()
            ->get();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
