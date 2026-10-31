<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «اعتبار پلتفرم» سالن (تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱، راه «ب» سقف ۱۰۰ میلیون تومانی زرین‌پال): دفتر اعتبار (مثبت = افزایش، منفی = مصرف)؛
 * مانده = جمع. ستون‌های قسط invoices و payment_transactions.installment در create همان جدول‌ها هستند (ادغام سوم migrationها؛ این فایل
 * قبلاً 2026_10_31_000001_add_invoice_installments_and_platform_credit بود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_credit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount'); // تومان؛ منفی = مصرف
            $table->string('reason', 40);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_credit_entries');
    }
};
