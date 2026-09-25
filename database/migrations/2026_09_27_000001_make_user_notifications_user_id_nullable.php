<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اعلانِ متخصصی که هنوز کاربر ندارد (specialists.user_id نال‌پذیر است) صاحب کاربری ندارد؛ user_id باید بتواند null باشد.
 * کلید خارجی به users سر جایش می‌ماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
