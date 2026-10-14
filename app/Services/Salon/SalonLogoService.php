<?php

namespace App\Services\Salon;

use App\Models\Salon;
use App\Support\SalonStorage;
use Illuminate\Http\Request;
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

    /** قانون لوگوی دوم (پس‌زمینه‌ی تیره)؛ همان محدودیت‌های لوگوی اصلی. */
    public const DARK_RULES = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'];

    public const DARK_MESSAGES = [
        'logo_dark.image' => 'لوگوی پس‌زمینه‌ی تیره باید یک فایل تصویری باشد.',
        'logo_dark.mimes' => 'فرمت لوگوی پس‌زمینه‌ی تیره باید PNG، JPG یا WEBP باشد.',
        'logo_dark.max' => 'حجم لوگوی پس‌زمینه‌ی تیره حداکثر ۲ مگابایت است.',
        'logo_dark.dimensions' => 'ابعاد لوگوی پس‌زمینه‌ی تیره حداقل ۶۴×۶۴ پیکسل باشد.',
    ];

    public function __construct(private readonly LogoToneAnalyzer $analyzer) {}

    public function replace(Salon $salon, UploadedFile $file): Salon
    {
        $old = $salon->logo_path;
        $path = $file->store(SalonStorage::directory($salon->id, 'branding'), 'public');

        // ⭐ بسته‌ی د: لحن روشنایی همان لحظه‌ی آپلود سنجیده و ذخیره می‌شود (نمایش هیچ‌وقت GD صدا نمی‌زند).
        $salon->update(['logo_path' => $path, 'logo_tone' => $this->analyzer->analyzeFile($file->getRealPath() ?: '')]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $salon;
    }

    /** حذف لوگو؛ لوگوی دوم (تیره) بدون لوگوی اصلی معنی ندارد و با آن پاک می‌شود. */
    public function remove(Salon $salon): Salon
    {
        if ($salon->logo_path) {
            Storage::disk('public')->delete($salon->logo_path);
            $salon->update(['logo_path' => null, 'logo_tone' => null]);
        }

        return $this->removeDark($salon);
    }

    /**
     * فرم‌های تنظیمات سالن (مدیر) و سوپرادمین: logo / remove_logo / logo_dark / remove_logo_dark.
     * لوگوی دوم فقط کنار لوگوی اصلی پذیرفته می‌شود؛ خطا (برای فیلد logo_dark) یا null.
     */
    public function syncFromRequest(Salon $salon, Request $request): ?string
    {
        $removingMain = ! $request->hasFile('logo') && $request->boolean('remove_logo');

        if ($request->hasFile('logo_dark') && ($removingMain || (! $salon->logo_path && ! $request->hasFile('logo')))) {
            return 'لوگوی پس‌زمینه‌ی تیره فقط کنار لوگوی اصلی پذیرفته می‌شود؛ اول لوگوی اصلی را بارگذاری کنید.';
        }

        if ($request->hasFile('logo')) {
            $this->replace($salon, $request->file('logo'));
        } elseif ($removingMain) {
            $this->remove($salon);
        }

        if ($request->hasFile('logo_dark')) {
            $this->replaceDark($salon, $request->file('logo_dark'));
        } elseif ($request->boolean('remove_logo_dark')) {
            $this->removeDark($salon);
        }

        return null;
    }

    /** ⭐ بسته‌ی د: لوگوی دوم اختیاری برای پس‌زمینه‌ی تیره (تم تیره‌ی سایت و اپ‌ها). */
    public function replaceDark(Salon $salon, UploadedFile $file): Salon
    {
        $old = $salon->logo_dark_path;
        $path = $file->store(SalonStorage::directory($salon->id, 'branding'), 'public');

        $salon->update(['logo_dark_path' => $path, 'logo_dark_tone' => $this->analyzer->analyzeFile($file->getRealPath() ?: '')]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $salon;
    }

    public function removeDark(Salon $salon): Salon
    {
        if ($salon->logo_dark_path) {
            Storage::disk('public')->delete($salon->logo_dark_path);
            $salon->update(['logo_dark_path' => null, 'logo_dark_tone' => null]);
        }

        return $salon;
    }

    /**
     * سنجش دوباره‌ی لوگوهای ذخیره‌شده (دستور salons:analyze-logos برای لوگوهای پیش از بسته‌ی د).
     *
     * @return bool آیا چیزی برای سنجیدن بود
     */
    public function analyzeStored(Salon $salon): bool
    {
        $disk = Storage::disk('public');
        $changes = [];

        foreach (['logo_path' => 'logo_tone', 'logo_dark_path' => 'logo_dark_tone'] as $pathColumn => $toneColumn) {
            if ($salon->{$pathColumn} && $disk->exists($salon->{$pathColumn})) {
                $changes[$toneColumn] = $this->analyzer->analyzeBytes((string) $disk->get($salon->{$pathColumn}));
            }
        }

        if ($changes === []) {
            return false;
        }

        $salon->forceFill($changes)->saveQuietly();

        return true;
    }
}
