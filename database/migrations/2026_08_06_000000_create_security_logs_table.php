<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Previously, SecurityLogService only wrote to the log file (Log::channel('security')),
     * but SecurityController reads from a DB table with the same name (which never existed).
     * This table provides the actual storage point so that the security dashboard/history
     * actually shows real data.
     */
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // ⭐ بسته‌ی مجوز 5د: سالن جاری و رکورد موضوع کار کلیدی (نوبت، نظر، تیکت، پرداخت…) — ماده‌ی ۶۶۸ آیین دادرسی کیفری
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event');
            $table->string('level')->default('info'); // info | warning
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index('event');
            $table->index('level');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['salon_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
