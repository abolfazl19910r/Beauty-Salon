<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payments:reconcile (هر ۵ دقیقه، همه‌ی سالن‌ها) سه کوئری با status برابر/IN و بازه‌ی created_at/updated_at دارد؛ ایندکس موجود
 * (salon_id, status) برای کوئری بدون salon_id قابل استفاده نیست و هر سه کل جدول را می‌خواندند (۵۹۸ هزار ردیف در ۱۰۰۰ سالن).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index(['status', 'updated_at'], 'payment_transactions_status_updated_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_status_updated_at_index');
        });
    }
};
