<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیگیری خودکار مشتریانی که مدتی نیامده‌اند (تصمیم ۲۰۲۶-۱۰-۱۴): تنظیمات هر سالن، ثبت هر پیامک فرستاده‌شده (یک بار در هر
 * «دوره‌ی غیبت» = یک بار برای هر آخرین مراجعه) و انصراف مشتری از پیامک‌های تبلیغاتی.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winback_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->unique()->constrained('salons')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->unsignedSmallInteger('lapse_days')->default(60);
            $table->string('discount_type', 20)->nullable(); // null = بدون کد تخفیف، fixed یا percentage
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('discount_max_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('discount_valid_days')->default(14);
            $table->timestamps();
        });

        Schema::create('winback_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('last_visit_at');
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();
            $table->dateTime('sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'last_visit_at']);
            $table->index(['salon_id', 'sent_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('marketing_sms_opted_out_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_sms_opted_out_at');
        });
        Schema::dropIfExists('winback_messages');
        Schema::dropIfExists('winback_settings');
    }
};
