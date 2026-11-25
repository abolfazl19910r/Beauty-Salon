<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵ب: عکس کاور اختیاری سالن برای قاب بزرگ قالب‌های «روزنامه‌ی سالن» و «نمایشگاه». نبودش ← اولین عکس گالری،
 * نبود آن هم ← طرح پالت (Salon::coverUrl و TemplateHomeData).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('logo_dark_tone');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }
};
