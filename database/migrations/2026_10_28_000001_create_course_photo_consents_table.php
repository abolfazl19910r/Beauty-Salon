<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سابقه‌ی رضایت عکس دوره‌های درمانی (۲۰۲۶-۱۰-۲۸؛ ماده‌ی ۵۸ و ۵۹ قانون تجارت الکترونیکی و ماده‌ی ۸ درباره‌ی مبدأ و زمان سابقه).
 * هر درخواست کادر و هر تصمیم (موافقت، مخالفت، ثبت حضوری، پس گرفتن) یک ردیف است؛ customer_courses.photo_consent_at همچنان وضعیت
 * فعلی است و دروازه‌ی آپلود عکس از همان می‌خواند.
 *
 * status: pending (درخواست بی پاسخ) | granted | declined | withdrawn | cancelled (درخواست بازی که ثبت حضوری یا تصمیم دیگری جایش را گرفت)
 * source: app | web | sms_link | in_person (ثبت حضوری توسط کادر)؛ برای ردیف pending خالی.
 * token_hash: SHA-256 توکن لینک پیامک (خود توکن ذخیره نمی‌شود)؛ expires_at مهلت پاسخ (۷ روز).
 * ستون‌های زمان dateTime‌اند (نه timestamp) تا قاعده‌ی explicit_defaults_for_timestamp=OFF (XAMPP، خطای 1067) درگیرشان نشود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_photo_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();          // مشتری
            $table->string('status', 16);
            $table->string('source', 16)->nullable();
            $table->string('text_version', 32);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();
            $table->unsignedSmallInteger('send_count')->default(0);
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // کادر، فقط ثبت حضوری
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['customer_course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_photo_consents');
    }
};
