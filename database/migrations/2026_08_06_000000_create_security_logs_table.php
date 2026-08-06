<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * قبلاً SecurityLogService فقط به فایل لاگ (Log::channel('security')) می‌نوشت،
     * ولی SecurityController از یک جدول DB با همین نام (که هیچ‌وقت وجود نداشت) می‌خوند.
     * این جدول همون نقطه‌ی واقعی ذخیره‌سازی رو فراهم می‌کنه تا داشبورد امنیتی/تاریخچه
     * واقعاً داده‌ی واقعی نشون بده.
     */
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('level')->default('info'); // info | warning
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index('event');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
