<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * password_changed_at: was already read by SecurityController but never
     * had a column for it (and was never written anywhere).
     *
     * password_strength_score: The real bug discovered during this phase — the security score used to
     * recalculate the password strength directly from the stored HASH (not the password itself);
     * Since the bcrypt hash is always 60 characters long and full of uppercase/lowercase letters/numbers/special characters, this
     * calculation always gave almost the highest possible score, regardless of the actual strength of the user's password.
     * Correct solution: the score is calculated only once, at the moment of registration/password change (when the raw password itself is still
     * available, before it is hashed) and stored there; it is never rebuilt from the hash.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->unsignedTinyInteger('password_strength_score')->nullable()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'password_strength_score']);
        });
    }
};
