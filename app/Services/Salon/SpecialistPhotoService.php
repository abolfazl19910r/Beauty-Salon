<?php

namespace App\Services\Salon;

use App\Models\Specialist;
use App\Support\SalonStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * ⭐ عکس پروفایل متخصص (۲۰۲۶-۰۹-۲۴) — همون الگوی SalonLogoService: پوشه‌ی جدای سالن
 * (salons/{salon_id}/specialists)، پاک‌کردن فایل قبلی، و همون قوانین اعتبارسنجی (بدون SVG).
 * کش ۳۰ دقیقه‌ای بخش «متخصصین ما» صفحه‌ی اصلی همون سالن هم پاک می‌شه تا عکس جدید فوراً دیده بشه.
 */
class SpecialistPhotoService
{
    public const RULES = SalonLogoService::RULES;

    public const MESSAGES = [
        'photo.image' => 'عکس باید یک فایل تصویری باشد.',
        'photo.mimes' => 'فرمت عکس باید PNG، JPG یا WEBP باشد.',
        'photo.max' => 'حجم عکس حداکثر ۲ مگابایت است.',
        'photo.dimensions' => 'ابعاد عکس حداقل ۶۴×۶۴ پیکسل باشد.',
    ];

    public function replace(Specialist $specialist, UploadedFile $file): Specialist
    {
        $old = $specialist->photo_path;
        $path = $file->store(SalonStorage::directory($specialist->salon_id, 'specialists'), 'public');

        $specialist->forceFill(['photo_path' => $path])->save();

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        $this->forgetHomeCache($specialist);

        return $specialist;
    }

    public function remove(Specialist $specialist): Specialist
    {
        if ($specialist->photo_path) {
            Storage::disk('public')->delete($specialist->photo_path);
            $specialist->forceFill(['photo_path' => null])->save();
            $this->forgetHomeCache($specialist);
        }

        return $specialist;
    }

    /** ورودی فرم (photo / remove_photo) رو اعمال می‌کنه؛ آپلود جدید بر حذف اولویت داره. */
    public function applyFromRequest(Specialist $specialist, \Illuminate\Http\Request $request): void
    {
        if ($request->hasFile('photo')) {
            $this->replace($specialist, $request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $this->remove($specialist);
        }
    }

    private function forgetHomeCache(Specialist $specialist): void
    {
        Cache::forget("home_specialists:{$specialist->salon_id}");
    }
}
