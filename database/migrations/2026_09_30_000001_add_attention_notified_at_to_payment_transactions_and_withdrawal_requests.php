<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * زمان اعلان «نیاز به بررسی» به مالک سالن (AttentionNotifier) — هر مورد فقط یک بار اعلان می‌گیرد، حتی اگر دوباره
 * پرچم بخورد یا job/دستور دوباره اجرا شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->timestamp('attention_notified_at')->nullable()->after('needs_attention');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->timestamp('attention_notified_at')->nullable()->after('needs_manual_check');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('attention_notified_at');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn('attention_notified_at');
        });
    }
};
