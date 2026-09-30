<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_sms_usages', function (Blueprint $table) {
            $table->unsignedInteger('otp_count')->default(0)->after('used_count');
        });
    }

    public function down(): void
    {
        Schema::table('salon_sms_usages', function (Blueprint $table) {
            $table->dropColumn('otp_count');
        });
    }
};
