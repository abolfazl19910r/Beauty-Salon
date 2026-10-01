<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دفتر اعتبار پیامک (۲۰۲۶-۰۹-۳۰): سالنی که سهمیه‌ی ماهانه‌اش تمام شده، بسته‌ی پیامک (بر حسب قطعه) می‌خرد یا سوپرادمین
 * اعتبار می‌دهد. موجودی در salons.sms_credit و مصرف ماه در salon_sms_usages.credit_used است. اعتبار منقضی نمی‌شود؛ مصرف
 * اول از سهمیه‌ی ماه، بعد از اعتبار.
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }
};
