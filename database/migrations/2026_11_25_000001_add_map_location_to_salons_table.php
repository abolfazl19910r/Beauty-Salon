<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مکان سالن روی نقشه برای «مسیریابی» مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵). migration جدا (نه ادغام در create)، چون
 * سرور production راه افتاده و باید بی migrate:fresh اعمال شود؛ فقط دو ستون nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->decimal('map_lat', 10, 7)->nullable()->after('address');
            $table->decimal('map_lng', 10, 7)->nullable()->after('map_lat');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['map_lat', 'map_lng']);
        });
    }
};
