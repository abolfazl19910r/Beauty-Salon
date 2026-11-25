<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * لینک نقشه‌ی سالن (آزمون ویندوز بسته‌ی ۴، ۲۰۲۶-۱۱-۲۵): مالک سالن معمولاً لینک کوتاه گوگل‌مپ (maps.app.goo.gl/…) دارد که
 * مختصات ندارد؛ خود لینک نگه داشته می‌شود و دکمه‌ی «مسیریابی» همان را باز می‌کند. migration جدا، چون map_lat/map_lng
 * روی سیستم توسعه اجرا شده است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('map_url', 500)->nullable()->after('map_lng');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('map_url');
        });
    }
};
