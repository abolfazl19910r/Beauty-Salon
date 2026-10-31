<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->string('code');
            // ⭐ یکتا در هر سالن، نه در کل جدول (۲۰۲۶-۱۰-۱۱): سالن دوم باید بتواند کد تخفیف (مثلاً NOROOZ) هم‌نام سالن دیگر بسازد.
            $table->unique(['salon_id', 'code'], 'discount_codes_salon_code_unique');
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('amount', 10, 2);
            $table->decimal('max_amount', 10, 2)
                ->nullable()
                ->comment('حداکثر مبلغ تخفیف (برای محدود کردن تخفیف‌های درصدی)');
            $table->integer('max_uses');
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            // دوره‌های درمانی (تصمیم ۹، ۲۰۲۶-۱۰-۱۴): روی خرید دوره هم معتبر؟ پیش‌فرض نه
            $table->boolean('valid_for_courses')->default(false);

            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
        });

        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // یکی از booking_id یا customer_course_id (استفاده از کد روی خرید دوره‌ی درمانی، ۲۰۲۶-۱۰-۱۴)
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount_saved', 10, 2);
            $table->timestamps();
            $table->foreignId('customer_course_id')->nullable()->constrained('customer_courses')->cascadeOnDelete();

            $table->unique(['discount_code_id', 'booking_id']);
            $table->unique(['discount_code_id', 'customer_course_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usages');
        Schema::dropIfExists('discount_codes');
    }
};
