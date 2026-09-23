<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ عکس پروفایل متخصص (۲۰۲۶-۰۹-۲۴). مسیر نسبی روی دیسک public در پوشه‌ی همون سالن
 * (salons/{salon_id}/specialists/...). null = بدون عکس؛ ویوها همون دایره‌ی حرف اول اسم رو نشون می‌دن.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
