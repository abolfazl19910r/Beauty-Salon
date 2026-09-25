<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ پرچم «نیاز به بررسی انسانی» (۲۰۲۶-۰۹-۲۶). ستون واقعی و index‌دار به‌جای کلیدی داخل ستون‌های json: پرس‌وجوی
 * کلید json با مقدار boolean در SQLite و MySQL و MariaDB یکسان رفتار نمی‌کنه، و این فیلترها باید همه‌جا درست باشن.
 * - payment_transactions.needs_attention: خودکارسازی کنار کشید (پاسخ تایید نرسید و بازه‌ی reconcile/برگشت تمام شد، یا
 *   آسان پرداخت تایید کرد ولی Settlement نشد). مدیر بررسی و «رسیدگی شد» می‌زنه.
 * - withdrawal_requests.needs_manual_check: نتیجه‌ی تسویه نامعلوم ماند (ProcessWithdrawalJob)؛ با تایید/رد دستی پاک می‌شه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->boolean('needs_attention')->default(false)->after('status');
            $table->index(['salon_id', 'needs_attention']);
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->boolean('needs_manual_check')->default(false)->after('status');
            $table->index('needs_manual_check');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['salon_id', 'needs_attention']);
            $table->dropColumn('needs_attention');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropIndex(['needs_manual_check']);
            $table->dropColumn('needs_manual_check');
        });
    }
};
