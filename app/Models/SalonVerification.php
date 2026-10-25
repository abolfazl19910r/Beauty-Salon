<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک ارسال مدارک احراز سالن (بسته‌ی مجوز 5د) — App\Services\Salon\SalonVerificationService. سطح پلتفرم: سالن خودش فقط ارسال‌های
 * خودش را از رابطه‌ی salon می‌بیند؛ صف بررسی مال ابرمدیر است.
 */
class SalonVerification extends Model
{
    /** انواع پروانه‌ی کسب سالن‌ها در درگاه ملی مجوزها (سند پروژه claude/Licensing_requirements.md بخش ۶) */
    public const LICENSE_TYPES = [
        'women_hairdressing' => 'آرایشگری زنانه',
        'women_hairdressing_tattoo' => 'آرایشگری زنانه و ترمیم و اصلاح ابرو (تاتو) با میکروپیگمانتاسیون',
        'epilation' => 'اپیلاسیون',
        'bridal_makeup' => 'آرایش عروس',
        'hairdressing_epilation' => 'آرایشگری و اپیلاسیون',
        'face_eyebrow' => 'اصلاح صورت بند و ابرو',
        'nail' => 'آرایش ناخن',
        'eyebrow_tattoo' => 'اصلاح و ترمیم ابرو (تاتو)',
        'men_barber' => 'پیرایشگری مردانه',
        'women_massage' => 'ماساژ آرامبخش زنانه',
        'mobile_vendor' => 'فروشندگی سیار و بی‌کانون (آرایشگری یا ماساژ سیار)',
        'other' => 'سایر (در توضیح رد/تأیید بررسی می‌شود)',
    ];

    protected $fillable = [
        'salon_id', 'submitted_by', 'owner_name', 'owner_national_code', 'license_type', 'license_unique_id', 'license_file_path',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'provider', 'provider_reference', 'provider_payload',
    ];

    protected $casts = [
        'owner_national_code' => 'encrypted',
        'provider_payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $hidden = ['owner_national_code'];

    protected static function booted(): void
    {
        static::saving(function (self $v) {
            if ($v->isDirty('owner_national_code')) {
                $v->national_code_hash = hash_hmac('sha256', (string) $v->owner_national_code, (string) config('app.key'));
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function licenseLabel(): string
    {
        return self::LICENSE_TYPES[$this->license_type] ?? (string) $this->license_type;
    }

    /** کد ملی با ستاره برای فهرست‌ها */
    public function maskedNationalCode(): string
    {
        $code = (string) $this->owner_national_code;

        return strlen($code) === 10 ? substr($code, 0, 3).'****'.substr($code, -3) : '—';
    }
}
