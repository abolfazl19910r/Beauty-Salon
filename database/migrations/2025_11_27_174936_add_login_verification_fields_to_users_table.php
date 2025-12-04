<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_verification_code', 6)->nullable()->after('verification_code_expire_at');
            $table->timestamp('login_verification_code_expire_at')->nullable()->after('login_verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_verification_code',
                'login_verification_code_expire_at'
            ]);
        });
    }
};
