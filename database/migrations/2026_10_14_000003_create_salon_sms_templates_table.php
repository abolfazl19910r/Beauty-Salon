<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * متن پیامک اختصاصی هر سالن (تصمیم ۲۰۲۶-۱۰-۱۴). نبودِ ردیف = متن پیش‌فرض (App\Support\Sms\SmsTemplates).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->text('body');
            $table->timestamps();

            $table->unique(['salon_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_sms_templates');
    }
};
