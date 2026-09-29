<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-line, consistent with wallet_settings — Previously, the "password freshness" period (90 days) was hardcoded in
     * SecurityController::calculateSecurityScore() and was not configurable by the admin
     * anywhere, even though the admin security settings page was routed for this purpose.
     */
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->id();
            // تنظیمات امنیتی مختص هر سالن (۲۰۲۶-۰۹-۲۷).
            $table->foreignId('salon_id')->nullable()->constrained('salons')->cascadeOnDelete();
            $table->unsignedSmallInteger('password_expiry_days')->default(90);
            $table->timestamps();

            $table->unique('salon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
