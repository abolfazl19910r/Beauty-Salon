<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ لوگوی اختصاصی هر سالن (۲۰۲۶-۰۹-۲۴) — مثل name/address، مال همون سالن. مسیر نسبی روی دیسک
 * public (salons/{id}/branding/...، به App\Support\SalonStorage نگاه کن). null = سالن لوگو نداره و
 * ویوها همون آیکون پیش‌فرض قبلی رو نشون می‌دن.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
