<?php

namespace App\Services\Salon;

use App\Models\Salon;
use App\Support\SalonStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * بسته‌ی ۵ب: عکس کاور سالن (قاب بزرگ «روزنامه‌ی سالن» و «نمایشگاه»). مثل لوگو در salons/{id}/branding/ روی دیسک public؛
 * با جایگزینی یا حذف، فایل قبلی پاک می‌شود. SVG عمداً پذیرفته نیست (مستقیم از دیسک public سرو می‌شود).
 */
class SalonCoverService
{
    public const RULES = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:min_width=800,min_height=400'];

    public const MESSAGES = [
        'cover.image' => 'عکس کاور باید یک فایل تصویری باشد.',
        'cover.mimes' => 'فرمت عکس کاور باید PNG، JPG یا WEBP باشد.',
        'cover.max' => 'حجم عکس کاور حداکثر ۳ مگابایت است.',
        'cover.dimensions' => 'ابعاد عکس کاور حداقل ۸۰۰×۴۰۰ پیکسل باشد.',
    ];

    public function replace(Salon $salon, UploadedFile $file): Salon
    {
        $old = $salon->cover_path;
        $path = $file->store(SalonStorage::directory($salon->id, 'branding'), 'public');
        $salon->update(['cover_path' => $path]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $salon;
    }

    public function remove(Salon $salon): Salon
    {
        if ($salon->cover_path) {
            Storage::disk('public')->delete($salon->cover_path);
            $salon->update(['cover_path' => null]);
        }

        return $salon;
    }

    /** فرم «اطلاعات سالن»: cover / remove_cover. */
    public function syncFromRequest(Salon $salon, Request $request): void
    {
        if ($request->hasFile('cover')) {
            $this->replace($salon, $request->file('cover'));
        } elseif ($request->boolean('remove_cover')) {
            $this->remove($salon);
        }
    }
}
