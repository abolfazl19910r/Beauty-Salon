<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی 5ه (۲۰۲۶-۱۰-۲۸): «نحوه‌ی رزرو» هر سالن و برچسب پیش‌پرداخت هر نوبت.
 *
 * booking_policies (یک ردیف برای هر سالن، مثل wallet_settings؛ درصد و حداقل پیش‌پرداخت همان‌جا می‌مانند):
 * - payment_mode: none (پیش‌فرض، پرداخت کامل در سالن) یا prepay (پیش‌پرداخت آنلاین؛ فقط با امکان پرداخت آنلاین پلن + درگاه فعال).
 * - payment_mode_fallback_at: آخرین باری که سالن خودکار از prepay به none برگشت (درگاه یا امکان پلن از دست رفت) — برای هشدار پنل.
 * - سقف‌ها و مشتری بدقول: daily_limit (پیش‌فرض ۱)، active_limit (۳)، no_show_threshold (۲) در no_show_window_days (۹۰)،
 *   flagged_force_pending و flagged_active_limit (۱) برای سالن بی درگاه، attendance_check (off · flagged · all). سقف خالی/صفر = بی‌سقف.
 *
 * bookings.prepayment_required (برچسب نوبت موقع ثبت) و ایندکس bookings_user_time_index در create_bookings_table هستند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->unique()->constrained('salons')->cascadeOnDelete();
            $table->string('payment_mode', 16)->default('none');
            $table->timestamp('payment_mode_fallback_at')->nullable();
            $table->unsignedSmallInteger('daily_limit')->nullable()->default(1);
            $table->unsignedSmallInteger('active_limit')->nullable()->default(3);
            $table->unsignedSmallInteger('no_show_threshold')->nullable()->default(2);
            $table->unsignedSmallInteger('no_show_window_days')->default(90);
            $table->boolean('flagged_force_pending')->default(true);
            $table->unsignedSmallInteger('flagged_active_limit')->nullable()->default(1);
            $table->string('attendance_check', 16)->default('off');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_policies');
    }
};
