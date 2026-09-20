<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه». یک ردیف به‌ازای هر (سالن، ماه تقویمی) — نه بازه‌ی اشتراک
 * (۱/۳/۶/۱۲ ماهه)، چون سقف پیامک یک بودجه‌ی ماهانه‌ست، مستقل از این‌که سالن چه دوره‌ای خریده.
 * `period` به‌صورت رشته‌ی 'Y-m' (مثل '2026-09') ذخیره می‌شه — ساده‌تر از تاریخ کامل برای
 * unique-constraint و query. `notified_at` فقط یک‌بار در هر دوره پر می‌شه (وقتی سقف برای اولین
 * بار همون ماه رد شد) تا نوتیفیکیشن اتمام شارژ اسپم نشه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_sms_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7); // 'Y-m'
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['salon_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_sms_usages');
    }
};
