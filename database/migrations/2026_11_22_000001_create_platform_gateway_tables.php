<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درگاه پرداخت پلتفرم برای سالن‌ها (۲۰۲۶-۱۱-۲۲).
 *
 * سالنی که مرچنت خودش را ندارد درخواست می‌دهد؛ ابرمدیر با درصد کارمزد تأیید یا رد می‌کند. پرداخت مشتری‌های آن سالن با مرچنت
 * پلتفرم (PlatformZarinpal) گرفته می‌شود و هر تراکنش در payment_transactions با driver = platform و salon_id ثبت است؛ درصد کارمزد
 * لحظه‌ی ساخت تراکنش روی همان ردیف عکس‌برداری می‌شود (platform_fee_percent، platform_share_rial) تا تغییر بعدی درصد، حساب گذشته
 * را عوض نکند. تسویه با سالن در platform_settlements (دسته‌ای از تراکنش‌های تسویه‌نشده‌ی یک سالن، با مقصد بانکی همان لحظه).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_gateway_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            // pending · approved · rejected · revoked
            $table->string('status', 16)->default('pending');
            // iban · card · account — شماره‌ها رمزنگاری‌شده (encrypted cast)
            $table->string('payout_method', 16);
            $table->text('payout_iban')->nullable();
            $table->text('payout_card')->nullable();
            $table->text('payout_account')->nullable();
            $table->string('payout_bank', 64)->nullable();
            $table->string('account_holder', 190);
            $table->text('owner_note')->nullable();
            // درصد کارمزد پلتفرم از کل مبلغ هر پرداخت؛ با تأیید ابرمدیر
            $table->decimal('fee_percent', 5, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['salon_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('platform_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            // pending (ساخته شده، منتظر واریز) · paid · cancelled
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('transactions_count')->default(0);
            $table->unsignedBigInteger('gross_rial')->default(0);
            $table->unsignedBigInteger('platform_share_rial')->default(0);
            $table->unsignedBigInteger('salon_share_rial')->default(0);
            $table->timestamp('period_from')->nullable();
            $table->timestamp('period_to')->nullable();
            // مقصد بانکی لحظه‌ی ساخت (رمزنگاری‌شده) تا تغییر بعدی درخواست، سند این تسویه را عوض نکند
            $table->string('payout_method', 16)->nullable();
            $table->text('payout_destination')->nullable();
            $table->string('account_holder', 190)->nullable();
            $table->boolean('automatic')->default(false);
            $table->string('bank_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['salon_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->decimal('platform_fee_percent', 5, 2)->nullable()->after('fee_rial');
            $table->unsignedBigInteger('platform_share_rial')->nullable()->after('platform_fee_percent');
            $table->foreignId('platform_settlement_id')->nullable()->after('platform_share_rial')->constrained('platform_settlements')->nullOnDelete();
            $table->index(['driver', 'salon_id', 'status'], 'payment_transactions_driver_salon_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_driver_salon_status_index');
            $table->dropConstrainedForeignId('platform_settlement_id');
            $table->dropColumn(['platform_fee_percent', 'platform_share_rial']);
        });
        Schema::dropIfExists('platform_settlements');
        Schema::dropIfExists('platform_gateway_requests');
    }
};
