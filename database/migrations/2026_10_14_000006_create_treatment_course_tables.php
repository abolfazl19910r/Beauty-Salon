<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دوره‌های درمانی چندجلسه‌ای (بسته‌ی ب، تصمیم‌های ۲۰۲۶-۱۰-۱۴). همه تننت (salon_id). سه جدول پایه (course_packages، course_settings،
 * customer_courses) و ستون‌های دوره‌ی bookings/discount_codes/discount_usages در create خود آن‌ها هستند (ادغام سوم migrationها،
 * ۲۰۲۶-۱۰-۳۱): 2024_01_25_190700_create_bookings_table و 2024_01_25_190900_create_discount_system_tables. اینجا: پرداخت‌ها (کل،
 * پیش‌پرداخت، باقی؛ به payment_transactions وصل است، پس بعد از آن)، یادداشت و عکس هر جلسه (به bookings وصل‌اند) و یادآوری‌ها.
 * عکس‌ها فقط روی دیسک خصوصی course_photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_course_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('customer_course_id')->constrained('customer_courses')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('wallet_amount', 15, 2)->default(0);
            $table->string('method', 20); // gateway, wallet, wallet_gateway, in_person, full_discount
            $table->foreignId('payment_transaction_id')->nullable()->unique()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'created_at']);
        });

        Schema::create('course_session_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('customer_course_id')->constrained('customer_courses')->cascadeOnDelete();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('specialist_id')->nullable()->constrained('specialists')->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('visible_to_customer')->default(false);
            $table->timestamps();
        });

        Schema::create('course_session_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('customer_course_id')->constrained('customer_courses')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('kind', 10); // before, after
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id', 'kind']);
        });

        Schema::create('course_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_course_id')->constrained('customer_courses')->cascadeOnDelete();
            $table->string('kind', 30); // next_session, next_session_followup, expiring
            $table->unsignedSmallInteger('session_number')->default(0);
            $table->dateTime('sent_at');
            $table->unsignedSmallInteger('sms_parts')->default(0);
            $table->timestamps();

            $table->unique(['customer_course_id', 'kind', 'session_number'], 'course_reminders_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reminders');
        Schema::dropIfExists('course_session_photos');
        Schema::dropIfExists('course_session_notes');
        Schema::dropIfExists('customer_course_payments');
    }
};
