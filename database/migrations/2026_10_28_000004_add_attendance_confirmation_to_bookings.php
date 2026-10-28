<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی 5ه، تصمیم ۱۶ (۳): «تأیید حضور با لینک». attendance_requested_at = پیامک/پوش لینک واقعاً رفت (فقط آن‌وقت لغو خودکار ممکن است)،
 * attendance_confirmed_at = مشتری تأیید کرد، attendance_token_hash = هش توکن لینک (توکن خام نگه داشته نمی‌شود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('attendance_requested_at')->nullable();
            $table->timestamp('attendance_confirmed_at')->nullable();
            $table->string('attendance_token_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['attendance_token_hash']);
            $table->dropColumn(['attendance_requested_at', 'attendance_confirmed_at', 'attendance_token_hash']);
        });
    }
};
