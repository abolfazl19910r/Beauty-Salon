<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): اپ‌ها در مارکت منتشر نمی‌شوند؛ ابرمدیر APK هر اپ (مشتری «ماهرو»، همکار «ماهرو همکار») را آپلود می‌کند و
 * صفحه‌ی «دانلود اپ ماهرو» (/app) آخرین نسخه‌ی منتشرشده‌ی هر اپ را می‌دهد. اپ با /api/v1/app/latest از نسخه‌ی تازه باخبر می‌شود.
 * سطح پلتفرم (بدون salon_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('variant', 10);
            $table->string('version_name', 32);
            $table->unsignedInteger('version_code');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64);
            $table->text('notes')->nullable();
            // نسخه‌ی اجباری: اپ قدیمی‌تر از این تا به‌روز نشود ادامه نمی‌دهد (برای تغییر ناسازگار API)
            $table->boolean('is_required')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['variant', 'version_code']);
            $table->index(['variant', 'is_published', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
