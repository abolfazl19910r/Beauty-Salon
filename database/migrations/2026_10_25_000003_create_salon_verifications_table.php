<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * احراز هویت مالک و پروانه‌ی کسب سالن (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵) — دستورالعمل ساماندهی سکوهای اینترنتی (نامه‌ی ۱/۱۴۱۶۳۴): بند ۲
 * احراز هویت تأمین‌کننده، بند ۴ کنترل پروانه‌ی کسب از طریق امتا. هر ارسال یک ردیف (سابقه‌ی رد و ارسال دوباره می‌ماند).
 *
 * مرحله‌ی الف (الان): دستی — ابرمدیر تأیید یا رد می‌کند (provider = manual). مرحله‌ی ب: provider/provider_reference/provider_payload
 * برای امتا یا شاهکار (App\Contracts\SalonIdentityVerifier). کد ملی رمزنگاری‌شده (APP_KEY) و HMAC آن برای یکتایی/جست‌وجو.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_name', 190);
            $table->text('owner_national_code');              // encrypted
            $table->char('national_code_hash', 64)->index();
            $table->string('license_type', 64);
            $table->string('license_unique_id', 64);
            $table->string('license_file_path')->nullable();  // دیسک خصوصی local
            $table->string('status', 16)->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('provider', 32)->default('manual');
            $table->string('provider_reference', 128)->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_verifications');
    }
};
