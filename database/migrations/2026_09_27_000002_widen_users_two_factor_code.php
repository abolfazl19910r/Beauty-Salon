<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TWO_FACTOR_CODE_LENGTH می‌تونه بیشتر از ۶ باشه؛ ستون ۶کاراکتری روی MySQL/MariaDB کد بلندتر رو با خطای 1406 رد می‌کرد.
 * هم‌اندازه‌ی TwoFactorAuthService::MAX_CODE_LENGTH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_code', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_code', 6)->nullable()->change();
        });
    }
};
