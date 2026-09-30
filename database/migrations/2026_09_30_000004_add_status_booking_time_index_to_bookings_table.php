<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دو کار زمان‌بندی‌شده روی نوبت‌های همه‌ی سالن‌ها (بدون salon_id) کل جدول bookings را می‌خواندند (اندازه‌گیری ۲۰۲۶-۰۹-۳۰،
 * ۱۰۰۰ سالن / ۶۹۴ هزار نوبت): bookings:send-reminders هر ۱۰ دقیقه (status + بازه‌ی booking_time) و CancelUnpaidBookings هر ۵ دقیقه
 * (status = pending_payment). هر دو با status برابر شروع می‌شوند؛ یک ایندکس هر دو را پوشش می‌دهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'booking_time'], 'bookings_status_booking_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_status_booking_time_index');
        });
    }
};
