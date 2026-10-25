<?php

namespace App\Services\Salon;

use App\Models\Salon;
use App\Models\SalonVerification;
use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\SecurityLogService;
use App\Services\SMSService;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Services\SuperAdmin\SuperAdminNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * احراز سالن، مرحله‌ی الف (بسته‌ی مجوز 5د): مالک از پنل مدارک را می‌فرستد ← «در انتظار تأیید» ← ابرمدیر تأیید یا رد (با دلیل).
 * تا تأیید، صفحه‌ی عمومی و رزرو سالن بسته است (ResolveSalonFromRoute)؛ پنل مدیر باز می‌ماند تا سالن آماده شود.
 * فایل پروانه روی دیسک خصوصی local (storage/app/private)، هرگز روی public.
 */
class SalonVerificationService
{
    public const DISK = 'local';

    /** @param array{owner_name: string, owner_national_code: string, license_type: string, license_unique_id: string} $data */
    public function submit(Salon $salon, User $by, array $data, ?UploadedFile $file): SalonVerification
    {
        $path = $file ? $file->storeAs('salon-verifications/'.$salon->id, Str::uuid().'.'.strtolower($file->extension() ?: 'bin'), self::DISK) : null;

        try {
            $verification = DB::transaction(function () use ($salon, $by, $data, $path) {
                $verification = SalonVerification::create([
                    'salon_id' => $salon->id,
                    'submitted_by' => $by->id,
                    'owner_name' => $data['owner_name'],
                    'owner_national_code' => IranianNationalCode::normalize($data['owner_national_code']),
                    'license_type' => $data['license_type'],
                    'license_unique_id' => trim($data['license_unique_id']),
                    'license_file_path' => $path,
                    'status' => 'pending',
                    'provider' => 'manual',
                ]);
                $salon->forceFill(['verification_status' => 'pending', 'verified_at' => null])->save();

                return $verification;
            });
        } catch (\Throwable $e) {
            $path && Storage::disk(self::DISK)->delete($path);
            throw $e;
        }

        app(SecurityLogService::class)->logAction('salon_verification_submitted', $verification);
        app(SuperAdminNotifier::class)->send('verification', sprintf('مدارک احراز سالن «%s» برای بررسی رسید.', $salon->name),
            route('superadmin.verifications.show', $verification->id, false), $salon->id);

        return $verification;
    }

    public function approve(SalonVerification $verification, User $reviewer): void
    {
        DB::transaction(function () use ($verification, $reviewer) {
            $verification->update(['status' => 'approved', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => null]);
            $verification->salon->forceFill(['verification_status' => 'approved', 'verified_at' => now()])->save();
        });

        app(SuperAdminAudit::class)->record('salon.verification', 'تأیید احراز سالن', $verification->salon_id);
        $this->tellOwner($verification->salon, sprintf('احراز سالن «%s» تأیید شد؛ صفحه‌ی سالن و رزرو آنلاین باز است. %s', $verification->salon->name, config('brand.name')));
    }

    public function reject(SalonVerification $verification, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->update(['status' => 'rejected', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'rejection_reason' => $reason]);
            $verification->salon->forceFill(['verification_status' => 'rejected', 'verified_at' => null])->save();
        });

        app(SuperAdminAudit::class)->record('salon.verification', 'رد احراز سالن: '.$reason, $verification->salon_id);
        $this->tellOwner($verification->salon, sprintf('احراز سالن «%s» تأیید نشد: %s. از پنل ← احراز سالن دوباره بفرستید. %s',
            $verification->salon->name, Str::limit($reason, 80), config('brand.name')));
    }

    /** پیامک به مالک — هزینه‌ی پلتفرم؛ خطا کار اصلی را نمی‌شکند */
    private function tellOwner(Salon $salon, string $text): void
    {
        try {
            if ($phone = $salon->owner()?->phone) {
                app(SMSService::class)->send($phone, $text);
            }
        } catch (\Throwable $e) {
            Log::warning('salon verification SMS failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
        }
    }
}
