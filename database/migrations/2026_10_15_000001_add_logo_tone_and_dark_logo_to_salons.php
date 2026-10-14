<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ بسته‌ی د — خوانایی لوگو (۲۰۲۶-۱۰-۱۵): لحن روشنایی لوگو (light/dark/mid، با GD هنگام آپلود) و لوگوی دوم اختیاری برای
 * پس‌زمینه‌ی تیره. لوگوهای موجود را یک بار `php artisan salons:analyze-logos` می‌سنجد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('logo_tone', 8)->nullable()->after('logo_path');
            $table->string('logo_dark_path')->nullable()->after('logo_tone');
            $table->string('logo_dark_tone', 8)->nullable()->after('logo_dark_path');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['logo_tone', 'logo_dark_path', 'logo_dark_tone']);
        });
    }
};
