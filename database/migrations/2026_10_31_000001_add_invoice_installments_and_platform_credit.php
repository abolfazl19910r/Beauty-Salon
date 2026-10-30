<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پرداخت قسطی فاکتور اشتراک/ارتقا و «اعتبار پلتفرم» سالن (تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱، راه «ب» سقف ۱۰۰ میلیون تومانی زرین‌پال).
 * invoices: credit_applied (تومان از اعتبار پلتفرم، هنگام ساخت رزرو می‌شود)، paid_amount (جمع قسط‌های پرداخت‌شده)، installments،
 * installments_paid، partial_expires_at (۷ روز بعد از اولین قسط). payment_transactions.installment = شماره‌ی قسط.
 * platform_credit_entries: دفتر اعتبار (مثبت = افزایش، منفی = مصرف)؛ مانده = جمع.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_applied')->default(0)->after('amount');
            $table->unsignedBigInteger('paid_amount')->default(0)->after('credit_applied');
            $table->unsignedTinyInteger('installments')->default(1)->after('paid_amount');
            $table->unsignedTinyInteger('installments_paid')->default(0)->after('installments');
            $table->timestamp('partial_expires_at')->nullable()->after('paid_at');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('installment')->nullable()->after('purpose');
        });

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

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('installment');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['credit_applied', 'paid_amount', 'installments', 'installments_paid', 'partial_expires_at']);
        });
    }
};
