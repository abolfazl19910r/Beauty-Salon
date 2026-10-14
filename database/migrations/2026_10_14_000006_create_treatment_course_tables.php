<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دوره‌های درمانی چندجلسه‌ای (بسته‌ی ب، تصمیم‌های ۲۰۲۶-۱۰-۱۴). همه تننت (salon_id). یک بسته = یک خدمت × N جلسه.
 * customer_courses «پرونده‌ی» خرید هر مشتری است با snapshot بسته در لحظه‌ی خرید؛ پرداخت‌ها (کل، پیش‌پرداخت، باقی) در
 * customer_course_payments. هر نوبتِ از دوره customer_course_id و شماره‌ی جلسه دارد. عکس‌ها فقط روی دیسک خصوصی course_photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('beauty_services')->cascadeOnDelete();
            $table->foreignId('specialist_id')->nullable()->constrained('specialists')->nullOnDelete();
            $table->string('name', 150);
            $table->string('description', 2000)->nullable();
            $table->unsignedSmallInteger('sessions_count');
            $table->decimal('price', 15, 2);
            // null = فقط پرداخت کل؛ وگرنه حداقل درصد پیش‌پرداخت (تصمیم ۸)
            $table->unsignedTinyInteger('min_prepayment_percent')->nullable();
            $table->unsignedSmallInteger('interval_days');
            $table->unsignedSmallInteger('min_interval_days');
            $table->unsignedSmallInteger('validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'is_active', 'is_public']);
        });

        Schema::create('course_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->unique()->constrained('salons')->cascadeOnDelete();
            // تصمیم ۷: جلسه‌ی لغو دیرهنگام مشتری سوخت شود؟ پیش‌فرض نه
            $table->boolean('burn_late_cancellation')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_package_id')->constrained('course_packages')->restrictOnDelete();
            // snapshot بسته در لحظه‌ی خرید
            $table->string('name', 150);
            $table->foreignId('service_id')->constrained('beauty_services')->restrictOnDelete();
            $table->foreignId('specialist_id')->nullable()->constrained('specialists')->nullOnDelete();
            $table->unsignedSmallInteger('sessions_total');
            $table->unsignedSmallInteger('interval_days');
            $table->unsignedSmallInteger('min_interval_days');
            $table->unsignedSmallInteger('validity_days')->nullable();
            $table->decimal('list_price', 15, 2);
            $table->string('discount_code', 50)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('price_total', 15, 2); // پس از تخفیف
            $table->decimal('per_session_value', 15, 2);
            $table->decimal('min_prepayment', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->unsignedSmallInteger('sessions_used')->default(0);
            $table->unsignedSmallInteger('sessions_burned')->default(0);
            $table->string('status', 20)->default('pending_payment'); // pending_payment, active, completed, expired, cancelled
            $table->dateTime('purchased_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_session_at')->nullable();
            $table->dateTime('next_due_at')->nullable();
            $table->dateTime('photo_consent_at')->nullable();
            $table->foreignId('photo_consent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();
            $table->decimal('refunded_amount', 15, 2)->default(0);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'status', 'next_due_at']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

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

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('customer_course_id')->nullable()->constrained('customer_courses')->nullOnDelete();
            $table->unsignedSmallInteger('course_session_number')->nullable();
            // جلسه‌ی دوره که با لغو دیرهنگام سوخت شد (تصمیم ۷)
            $table->boolean('course_session_burned')->default(false);
            // درآمد متخصص از این جلسه‌ی دوره ثبت شد (یک بار برای هر نوبت)
            $table->dateTime('course_income_at')->nullable();
            $table->index(['customer_course_id', 'status']);
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

        Schema::table('discount_codes', function (Blueprint $table) {
            // تصمیم ۹: روی خرید دوره هم معتبر؟ پیش‌فرض نه
            $table->boolean('valid_for_courses')->default(false);
        });

        Schema::table('discount_usages', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->change();
            $table->foreignId('customer_course_id')->nullable()->constrained('customer_courses')->cascadeOnDelete();
            $table->unique(['discount_code_id', 'customer_course_id']);
        });
    }

    public function down(): void
    {
        // FK پیش از ایندکسی که پشتیبانش است؛ booking_id عمداً nullable می‌ماند (ردیف‌های دوره ممکن است مانده باشند)
        Schema::table('discount_usages', function (Blueprint $table) {
            $table->dropForeign(['customer_course_id']);
            $table->dropUnique(['discount_code_id', 'customer_course_id']);
            $table->dropColumn('customer_course_id');
        });
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn('valid_for_courses');
        });
        Schema::dropIfExists('course_reminders');
        Schema::dropIfExists('course_session_photos');
        Schema::dropIfExists('course_session_notes');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['customer_course_id']);
            $table->dropIndex(['customer_course_id', 'status']);
            $table->dropColumn(['customer_course_id', 'course_session_number', 'course_session_burned', 'course_income_at']);
        });
        Schema::dropIfExists('customer_course_payments');
        Schema::dropIfExists('customer_courses');
        Schema::dropIfExists('course_settings');
        Schema::dropIfExists('course_packages');
    }
};
