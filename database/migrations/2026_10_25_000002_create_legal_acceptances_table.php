<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پذیرش قوانین و حریم خصوصی (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵): چه کسی، کدام سند، کدام نسخه، کی، از کجا (IP، user agent) و از چه
 * راهی (وب، اپ، اپ قدیمی بی تیک = «ضمنی»). ماده‌ی ۸ قانون تجارت الکترونیکی: مبدأ و زمان سابقه نگه داشته شود.
 * user_id و salon_id بی کلید خارجی cascade: سابقه‌ی پذیرش با حذف حساب از بین نرود (حذف حساب ناشناس می‌کند).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('salon_id')->nullable()->index();
            $table->string('document', 32);       // terms | privacy | salon_terms
            $table->string('version', 32);
            $table->string('source', 32);         // web | app | app-implicit
            $table->string('app_version', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            // ⭐ useCurrent: DEFAULT صریح CURRENT_TIMESTAMP. بی‌این، در MySQL/MariaDB با
            // explicit_defaults_for_timestamp=OFF اولین TIMESTAMP جدول ضمناً ON UPDATE
            // CURRENT_TIMESTAMP هم می‌گرفت و زمان پذیرش (ماده‌ی ۸ قانون تجارت الکترونیکی) با هر آپدیت ردیف بی‌صدا عوض می‌شد.
            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'document', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_acceptances');
    }
};
