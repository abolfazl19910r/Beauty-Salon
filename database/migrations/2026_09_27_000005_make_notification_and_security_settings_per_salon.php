<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات اطلاع‌رسانی و تنظیمات امنیتی مال هر سالن (تصمیم ۲۰۲۶-۰۹-۲۷). ردیف بدون سالن فقط در بافت بدون سالن
 * (کنسول، سوپرادمین) خوانده می‌شود؛ هر سالن ردیف‌های خودش را دارد (اولین بار با مقدار پیش‌فرض ساخته می‌شود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropUnique(['event_key']);
        });
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
            $table->unique(['salon_id', 'event_key']);
        });

        Schema::table('security_settings', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
            $table->unique('salon_id');
        });
    }

    public function down(): void
    {
        // روی MySQL/MariaDB ایندکس یکتا پشتیبان کلید خارجی است؛ اول کلید خارجی، بعد ایندکس، بعد ستون.
        Schema::table('security_settings', function (Blueprint $table) {
            $table->dropForeign(['salon_id']);
            $table->dropUnique(['salon_id']);
            $table->dropColumn('salon_id');
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropForeign(['salon_id']);
            $table->dropUnique(['salon_id', 'event_key']);
            $table->dropColumn('salon_id');
        });
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->unique('event_key');
        });
    }
};
