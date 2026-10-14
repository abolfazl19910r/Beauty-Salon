<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ج «ارسال دستی به مشتری‌های قدیمی» (۲۰۲۶-۱۰-۱۴): کمپین پیامکی دستی و گیرنده‌های ثبت‌شده‌ی هر کمپین.
 * گیرنده‌ها لحظه‌ی شروع ثبت می‌شوند و هر کدام فقط یک بار (یکتا روی کمپین + مشتری) پیامک می‌گیرند. پیامک‌های کمپین در
 * winback_messages نمی‌روند (یکتای user_id + last_visit_at آن برای پیگیری خودکار است)؛ خنک‌شدن ۹۰ روزه هر دو جدول را می‌بیند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winback_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('last_visit_from_days');
            $table->unsignedSmallInteger('last_visit_to_days');
            $table->json('service_ids')->nullable();
            $table->unsignedSmallInteger('min_visits')->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('discount_max_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('discount_valid_days')->default(14);
            $table->text('message');
            $table->string('status', 20)->default('scheduled'); // scheduled, sending, paused, done, cancelled
            $table->string('pause_reason', 30)->nullable();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('max_recipients');
            $table->unsignedSmallInteger('targeted')->default(0);
            $table->unsignedSmallInteger('sent')->default(0);
            $table->unsignedSmallInteger('skipped')->default(0);
            $table->unsignedSmallInteger('failed')->default(0);
            $table->unsignedInteger('parts_used')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('winback_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('winback_campaign_id')->constrained('winback_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('last_visit_at');
            $table->string('status', 20)->default('pending'); // pending, sending, sent, skipped, failed
            $table->string('skip_reason', 30)->nullable();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();
            $table->unsignedTinyInteger('sms_parts')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['winback_campaign_id', 'user_id'], 'winback_campaign_recipient_once');
            $table->index(['winback_campaign_id', 'status']);
            $table->index(['user_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winback_campaign_recipients');
        Schema::dropIfExists('winback_campaigns');
    }
};
