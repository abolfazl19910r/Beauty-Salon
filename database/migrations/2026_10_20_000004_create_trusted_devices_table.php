<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۶ (۲۰۲۶-۱۰-۲۰): «مرا به خاطر بسپار» ورود وب (مشتری و کادر) — کد پیامکی ورود فقط بار اول روی هر دستگاه، مثل اپ (تصمیم ۴).
 * کوکی mahru_device_{user_id} توکن تصادفی دارد و اینجا فقط هش آن؛ ۹۰ روز از آخرین استفاده. تغییر رمز همه را باطل می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
