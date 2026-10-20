<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک نسخه‌ی APK آپلودشده‌ی ابرمدیر (بسته‌ی ۵). سطح پلتفرم؛ TenantModelClassificationTest.
 *
 * @property string $variant customer|staff
 */
class AppRelease extends Model
{
    public const VARIANTS = [
        'customer' => ['name' => 'ماهرو', 'audience' => 'مشتری‌های سالن‌ها', 'package_config' => 'api.customer_app_package'],
        'staff' => ['name' => 'ماهرو همکار', 'audience' => 'متخصص‌های سالن‌ها', 'package_config' => 'api.staff_app_package'],
    ];

    protected $fillable = [
        'variant', 'version_name', 'version_code', 'file_path', 'file_size', 'sha256', 'notes',
        'is_required', 'is_published', 'download_count', 'uploaded_by',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'file_size' => 'integer',
        'is_required' => 'boolean',
        'is_published' => 'boolean',
        'download_count' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** آخرین نسخه‌ی منتشرشده‌ی یک اپ */
    public static function latestFor(string $variant): ?self
    {
        return self::query()->published()->where('variant', $variant)->orderByDesc('version_code')->first();
    }

    public static function appName(string $variant): string
    {
        return self::VARIANTS[$variant]['name'] ?? $variant;
    }

    public static function packageFor(string $variant): string
    {
        return (string) config(self::VARIANTS[$variant]['package_config']);
    }

    /** نام فایل دانلودی، مثل mahru-0.3.0.apk / mahru-staff-0.3.0.apk */
    public function downloadName(): string
    {
        $slug = $this->variant === 'staff' ? 'mahru-staff' : 'mahru';
        $version = preg_replace('/[^0-9A-Za-z.\-]/', '', $this->version_name) ?: (string) $this->version_code;

        return "{$slug}-{$version}.apk";
    }

    public function sizeLabel(): string
    {
        $mb = $this->file_size / 1048576;

        return to_persian_num(number_format($mb, $mb < 10 ? 1 : 0)).' مگابایت';
    }
}
