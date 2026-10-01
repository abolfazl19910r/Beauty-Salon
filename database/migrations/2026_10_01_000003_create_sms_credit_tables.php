<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اعتبار پیامک خریده‌شده (۲۰۲۶-۰۹-۳۰): سالنی که سهمیه‌ی ماهانه‌اش تمام شده، بسته‌ی پیامک (بر حسب قطعه) می‌خرد یا سوپرادمین
 * اعتبار می‌دهد. اعتبار منقضی نمی‌شود؛ مصرف اول از سهمیه‌ی ماه، بعد از اعتبار.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->unsignedInteger('sms_credit')->default(0)->after('sms_quota_per_month');
        });

        Schema::table('salon_sms_usages', function (Blueprint $table) {
            $table->unsignedInteger('credit_used')->default(0)->after('used_count');
            $table->timestamp('warned_at')->nullable()->after('notified_at');
        });

        Schema::create('sms_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('parts');
            $table->unsignedBigInteger('amount')->default(0); // تومان؛ ۰ برای اعطای سوپرادمین
            $table->string('source', 16);                      // online | grant
            $table->string('status', 16)->default('pending');  // pending | paid | failed
            $table->string('authority')->nullable();
            $table->string('ref_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_purchases');

        Schema::table('salon_sms_usages', function (Blueprint $table) {
            $table->dropColumn(['credit_used', 'warned_at']);
        });

        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('sms_credit');
        });
    }
};
