<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): توقف موقت رزرو سالن — جدا از is_suspended (که کل سالن را می‌بندد). paused_at پر = رزرو تازه بسته؛
 * paused_until اختیاری (بازگشایی خودکار)، pause_reason اختیاری و به مشتری نشان داده می‌شود، paused_by: owner (مدیر سالن) یا platform
 * (ابرمدیر؛ مدیر نمی‌تواند بازش کند).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('is_suspended');
            $table->timestamp('paused_until')->nullable()->after('paused_at');
            $table->string('pause_reason', 200)->nullable()->after('paused_until');
            $table->string('paused_by', 10)->nullable()->after('pause_reason');
            $table->foreignId('paused_by_user_id')->nullable()->after('paused_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by_user_id');
            $table->dropColumn(['paused_at', 'paused_until', 'pause_reason', 'paused_by']);
        });
    }
};
