<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تک‌ردیفی، هم‌الگو با wallet_settings — قبلاً مهلت «تازگی رمز عبور» (۹۰ روز) در
     * SecurityController::calculateSecurityScore() هاردکد بود و هیچ‌جا توسط ادمین
     * قابل‌تنظیم نبود، با اینکه صفحه‌ی تنظیمات امنیتی ادمین برای همین منظور روت شده بود.
     */
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('password_expiry_days')->default(90);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
