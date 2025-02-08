<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'order'
    ];

    public static function where(string $column, mixed $value): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()->where($column, $value);
    }

    public static function create(array $validated): static
    {
        $image = new static();
        $image->fill($validated);
        $image->save();
        return $image;
    }

    public static function orderBy(string $column): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()->orderBy($column);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
