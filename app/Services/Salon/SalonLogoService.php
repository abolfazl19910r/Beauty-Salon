<?php

namespace App\Services\Salon;

use App\Models\Salon;
use App\Support\SalonStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * ⭐ لوگوی اختصاصی هر سالن (۲۰۲۶-۰۹-۲۴). فایل در salons/{id}/branding/ روی دیسک public ذخیره
 * می‌شه؛ با آپلود لوگوی جدید یا حذف، فایل قبلی هم پاک می‌شه تا فایل یتیم نمونه.
 */
class SalonLogoService
{
    /** قانون اعتبارسنجی مشترک فرم ثبت‌نام عمومی و فرم‌های سوپرادمین. SVG عمداً مجاز نیست
     *  (روی دیسک public مستقیم سرو می‌شه و می‌تونه اسکریپت داشته باشه). */
    public const RULES = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'];

    public const MESSAGES = [
        'logo.image' => 'لوگو باید یک فایل تصویری باشد.',
        'logo.mimes' => 'فرمت لوگو باید PNG، JPG یا WEBP باشد.',
        'logo.max' => 'حجم لوگو حداکثر ۲ مگابایت است.',
        'logo.dimensions' => 'ابعاد لوگو حداقل ۶۴×۶۴ پیکسل باشد.',
    ];

    public function replace(Salon $salon, UploadedFile $file): Salon
    {
        $old = $salon->logo_path;
        $path = $file->store(SalonStorage::directory($salon->id, 'branding'), 'public');

        $salon->update(['logo_path' => $path]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $salon;
    }

    public function remove(Salon $salon): Salon
    {
        if ($salon->logo_path) {
            Storage::disk('public')->delete($salon->logo_path);
            $salon->update(['logo_path' => null]);
        }

        return $salon;
    }
}
