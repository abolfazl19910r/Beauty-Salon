<?php

namespace App\Services\Apps;

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ثبت و حذف نسخه‌های APK (بسته‌ی ۵). فایل روی دیسک خصوصی local (`app-releases/{variant}/…`) می‌ماند و فقط از مسیر دانلود
 * (AppDownloadController) با نام درست و شمارش دانلود داده می‌شود.
 */
class AppReleaseService
{
    public const DISK = 'local';

    /**
     * @param  array{variant: string, version_name?: ?string, version_code?: ?int, notes?: ?string, is_required?: bool}  $input
     */
    public function store(UploadedFile $file, array $input, ?User $uploader): AppRelease
    {
        $variant = $input['variant'];
        $manifest = ApkManifestReader::fromApk($file->getRealPath());

        if ($manifest === null) {
            // APK معتبر دست‌کم یک AndroidManifest.xml دارد؛ اگر اصلاً zip نیست، فایل اشتباه است
            $zip = new \ZipArchive;
            if ($zip->open($file->getRealPath()) !== true || $zip->locateName('AndroidManifest.xml') === false) {
                throw ValidationException::withMessages(['apk' => 'این فایل APK اندروید نیست.']);
            }
            $zip->close();
        }

        $expectedPackage = AppRelease::packageFor($variant);
        if ($manifest !== null && $manifest['package'] !== null && $manifest['package'] !== $expectedPackage) {
            throw ValidationException::withMessages([
                'apk' => "شناسه‌ی این APK «{$manifest['package']}» است، نه «{$expectedPackage}» (اپ «".AppRelease::appName($variant).'»). فایل اپ دیگر را انتخاب کرده‌اید؟',
            ]);
        }

        $versionCode = ($manifest['version_code'] ?? null) ?: ($input['version_code'] ?? null);
        $versionName = ($manifest['version_name'] ?? null) ?: ($input['version_name'] ?? null);

        if (! empty($input['version_code']) && $manifest !== null && $manifest['version_code'] && (int) $input['version_code'] !== $manifest['version_code']) {
            throw ValidationException::withMessages([
                'version_code' => 'شماره‌ی نسخه‌ی خود فایل '.$manifest['version_code'].' است؛ فیلد را خالی بگذارید یا همان را وارد کنید.',
            ]);
        }
        if (! $versionCode || ! $versionName) {
            throw ValidationException::withMessages([
                'version_code' => 'نسخه از خود فایل خوانده نشد؛ «شماره‌ی نسخه (versionCode)» و «نام نسخه» را دستی وارد کنید.',
            ]);
        }
        if (AppRelease::query()->where('variant', $variant)->where('version_code', $versionCode)->exists()) {
            throw ValidationException::withMessages([
                'apk' => "نسخه‌ی {$versionCode} این اپ قبلاً آپلود شده است. برای نسخه‌ی تازه versionCode را در app.config بالا ببرید.",
            ]);
        }

        $path = $file->storeAs('app-releases/'.$variant, Str::uuid().'.apk', self::DISK);

        return AppRelease::create([
            'variant' => $variant,
            'version_name' => mb_substr((string) $versionName, 0, 32),
            'version_code' => (int) $versionCode,
            'file_path' => $path,
            'file_size' => (int) $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'notes' => $input['notes'] ?? null,
            'is_required' => (bool) ($input['is_required'] ?? false),
            'is_published' => true,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    public function delete(AppRelease $release): void
    {
        Storage::disk(self::DISK)->delete($release->file_path);
        $release->delete();
    }

    /**
     * پاسخ «نسخه‌ی تازه» برای اپ: آخرین نسخه‌ی منتشرشده و این‌که نسخه‌ی گوشی اجباراً باید به‌روز شود (اگر هر نسخه‌ی اجباری
     * تازه‌تر از نسخه‌ی گوشی منتشر شده باشد، نه فقط آخرینی).
     *
     * @return array{latest: ?array<string, mixed>, update_available: bool, update_required: bool}
     */
    public function check(string $variant, ?int $currentVersionCode): array
    {
        $latest = AppRelease::latestFor($variant);
        if ($latest === null) {
            return ['latest' => null, 'update_available' => false, 'update_required' => false];
        }

        $available = $currentVersionCode !== null && $latest->version_code > $currentVersionCode;
        $required = $available && AppRelease::query()->published()->where('variant', $variant)
            ->where('version_code', '>', $currentVersionCode)->where('is_required', true)->exists();

        return [
            'latest' => [
                'version_name' => $latest->version_name,
                'version_code' => $latest->version_code,
                'notes' => $latest->notes,
                'size_bytes' => $latest->file_size,
                'sha256' => $latest->sha256,
                'published_at' => $latest->created_at?->toIso8601String(),
                'download_url' => \App\Support\CentralUrl::route('app-download.file', ['variant' => $variant]),
                'page_url' => \App\Support\CentralUrl::route('app-download.show'),
            ],
            'update_available' => $available,
            'update_required' => $required,
        ];
    }
}
