<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵ب: نمونه‌کار گالری می‌تواند (اختیاری) به یک خدمت و یک کارشناس وصل شود. قالب «نمایشگاه» با همین پیوند نام
 * کارشناس را روی عکس می‌نویسد، دکمه‌ی «این خدمت را با همین کارشناس رزرو کنید» می‌دهد و «N نمونه‌کار» را کنار هر
 * خدمت می‌شمارد. با حذف خدمت/کارشناس، عکس می‌ماند و فقط پیوندش خالی می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('image_path')
                ->constrained('beauty_services')->nullOnDelete();
            $table->foreignId('specialist_id')->nullable()->after('service_id')
                ->constrained('specialists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialist_id');
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
