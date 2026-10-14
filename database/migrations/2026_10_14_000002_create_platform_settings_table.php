<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات سطح پلتفرم که سوپرادمین از پنل عوض می‌کند (۲۰۲۶-۱۰-۱۴): راه‌های تماس صفحه‌ی فروش و گیرنده‌ی اطلاع‌رسانی درخواست دمو.
 * کلید/مقدار؛ مقدار خالی یعنی «از .env/config بخوان» (App\Support\PlatformSettings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
