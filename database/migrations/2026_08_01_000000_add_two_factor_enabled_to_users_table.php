<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R-Pint prep / secure-payment-2fa: `App\Services\TwoFactorAuthService::isEnabled()/enable()/disable()`
 * and `App\Http\Controllers\User\SecurityController` have always read/written `$user->two_factor_enabled`,
 * but this column never existed on the `users` table. Reading it silently returned null (Eloquent magic
 * getter, not a DB error) — meaning 2FA always appeared "off" for everyone. Calling enable()/disable()
 * would have thrown a hard SQL "Unknown column" error the first time anyone actually tried to use it.
 * This is a hard blocking prerequisite for the secure-payment/2FA checkout path to be usable at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_enabled');
        });
    }
};
