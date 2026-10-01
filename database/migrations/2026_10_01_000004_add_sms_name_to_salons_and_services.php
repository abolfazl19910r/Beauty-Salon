<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «نام کوتاه پیامکی» سالن و خدمت (۲۰۲۶-۰۹-۳۰): نام بلند پیامک را از ۷۰ نویسه (یک قطعه) رد می‌کرد. خالی = نام اصلی.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('sms_name', 20)->nullable()->after('name');
        });
        Schema::table('beauty_services', function (Blueprint $table) {
            $table->string('sms_name', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('beauty_services', fn (Blueprint $table) => $table->dropColumn('sms_name'));
        Schema::table('salons', fn (Blueprint $table) => $table->dropColumn('sms_name'));
    }
};
