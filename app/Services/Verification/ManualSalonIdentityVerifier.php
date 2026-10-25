<?php

namespace App\Services\Verification;

use App\Contracts\Verification\SalonIdentityVerifier;
use App\Models\SalonVerification;

/** مرحله‌ی الف: هیچ استعلام خودکاری نیست؛ ابرمدیر در صف «احراز سالن‌ها» تأیید یا رد می‌کند (بسته‌ی مجوز 5د) */
final class ManualSalonIdentityVerifier implements SalonIdentityVerifier
{
    public function name(): string
    {
        return 'manual';
    }

    public function check(SalonVerification $verification): VerificationOutcome
    {
        return VerificationOutcome::pending();
    }
}
