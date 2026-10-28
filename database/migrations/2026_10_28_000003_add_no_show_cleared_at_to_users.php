<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی 5ه، تصمیم ۱۶: «برداشتن محدودیت» مشتری بدقول توسط مدیر از صفحه‌ی همان مشتری. سابقه پاک نمی‌شود؛ «نیامد»های پیش از این لحظه
 * دیگر در شمارش بدقولی حساب نمی‌شوند (NoShowRecord).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('no_show_cleared_at')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'no_show_at'], 'bookings_user_no_show_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_user_no_show_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('no_show_cleared_at');
        });
    }
};
