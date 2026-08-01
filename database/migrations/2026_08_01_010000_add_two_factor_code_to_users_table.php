<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TwoFactorAuthService previously stored the OTP code via the generic Cache facade
 * (Cache::put("2fa:{id}", ...)). Whatever CACHE_STORE the local .env has configured
 * (the project's own .env.example ships with CACHE_STORE=array) determines whether
 * that value survives between the request that generates the code and the separate
 * request that verifies it. With the 'array' driver, cache is in-memory-per-request
 * only -- it evaporates the instant the request ends, so verification always saw
 * "not found" no matter how fast the user replied.
 *
 * The project already has a proven, persistent pattern for exactly this need:
 * users.login_verification_code / login_verification_code_expire_at, used by
 * PhoneVerificationService for the login OTP flow. This migration adds a separate,
 * dedicated pair of columns for the 2FA feature so the two flows never clobber
 * each other's codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_code', 6)->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_code_expires_at')->nullable()->after('two_factor_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_code', 'two_factor_code_expires_at']);
        });
    }
};
