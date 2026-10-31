<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * ⭐ دوره‌های درمانی چندجلسه‌ای (بسته‌ی ب، تصمیم‌های ۲۰۲۶-۱۰-۱۴) — سه جدول پایه‌ی دوره پیش از bookings ساخته می‌شوند (ادغام سوم
         * migrationها، ۲۰۲۶-۱۰-۳۱) تا bookings.customer_course_id و discount_usages.customer_course_id از همان create کلید خارجی داشته
         * باشند؛ بقیه‌ی جدول‌های دوره (پرداخت‌ها، یادداشت/عکس جلسه، یادآوری) در 2026_10_14_000006_create_treatment_course_tables. همه
         * تننت (salon_id). یک بسته = یک خدمت (+ extra_service_ids برای بسته‌ی چندخدمتی) × N جلسه. customer_courses «پرونده‌ی» خرید هر
         * مشتری است با snapshot بسته در لحظه‌ی خرید.
         */
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('beauty_services')->cascadeOnDelete();
            // بسته‌ی چندخدمتی: هر جلسه همه‌ی خدمت‌ها با هم در یک نوبت
            $table->json('extra_service_ids')->nullable();
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
            $table->json('extra_service_ids')->nullable();
            // مدت هر جلسه (جمع خدمت‌ها)؛ null = مدت خدمت اصلی
            $table->unsignedSmallInteger('session_duration')->nullable();
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

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('beauty_services');
            // مدت این نوبت (جمع خدمت‌های بسته‌ی چندخدمتی)؛ جای مدت خدمت را در هم‌پوشانی و ساعت‌های آزاد می‌گیرد. null = مدت خدمت
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->foreignId('specialist_id')->constrained('specialists');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('booking_time');
            // no_show («نیامد»): پیش‌پرداخت مال سالن، جلسه‌ی دوره می‌سوزد؛ active_slot_key فقط با cancelled آزاد می‌شود
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'pending_payment', 'completed', 'no_show'])->default('pending');
            // Purely for reporting/audit; has NO effect on slot-availability logic
            // (Specialist::getAvailableSlots() blocks on ANY non-cancelled booking for that
            // specialist/time regardless of source).
            $table->enum('source', ['online', 'phone', 'walk_in'])->default('online');
            $table->string('discount_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('prepayment_amount', 10, 2)->default(50000);
            // بسته‌ی 5ه: برچسب نوبت موقع ثبت (حالت «نحوه‌ی رزرو» سالن در آن لحظه)؛ لغو خودکار، دکمه‌ی پرداخت، پیامک، امتیاز و
            // جابه‌جایی به این نگاه می‌کنند، نه به حالت فعلی سالن (booking_policies).
            $table->boolean('prepayment_required')->default(false);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
            // درخواست نظرسنجی بعد از تکمیل نوبت (ReviewService) و زمان ثبت نظر
            $table->timestamp('review_sent_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('cancelled_by', ['customer', 'specialist', 'admin', 'system'])->nullable()->comment('شخصی که نوبت را لغو کرده');
            $table->text('cancellation_reason')->nullable()->comment('دلیل لغو نوبت');
            $table->timestamp('cancelled_at')->nullable()->comment('زمان لغو نوبت');
            $table->boolean('reminder_sent')->default(false);
            $table->enum('refund_status', ['pending', 'refunded', 'failed'])->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->string('refund_reference')->nullable();
            $table->json('refund_details')->nullable();
            $table->timestamps();
            // (active_slot_key — ستون تولیدی — پایین با after('updated_at') همین‌جا می‌نشیند)
            // جلسه‌ی دوره‌ی درمانی: شماره‌ی جلسه، سوخت با لغو دیرهنگام (تصمیم ۷)، و ثبت یک‌باره‌ی درآمد متخصص از جلسه
            $table->foreignId('customer_course_id')->nullable()->constrained('customer_courses')->nullOnDelete();
            $table->unsignedSmallInteger('course_session_number')->nullable();
            $table->boolean('course_session_burned')->default(false);
            $table->dateTime('course_income_at')->nullable();
            $table->dateTime('no_show_at')->nullable();
            $table->foreignId('no_show_by')->nullable()->constrained('users')->nullOnDelete();
            // بسته‌ی 5ه، «تأیید حضور با لینک»: requested = پیامک/پوش لینک واقعاً رفت (فقط آن‌وقت لغو خودکار ممکن است)،
            // confirmed = مشتری تأیید کرد، token_hash = هش توکن لینک (توکن خام نگه داشته نمی‌شود)
            $table->timestamp('attendance_requested_at')->nullable();
            $table->timestamp('attendance_confirmed_at')->nullable();
            $table->string('attendance_token_hash', 64)->nullable()->unique();

            $table->index('cancelled_by');
            $table->index('cancelled_at');
            // کارهای زمان‌بندی‌شده‌ی همه‌ی سالن‌ها (bookings:send-reminders با status + بازه‌ی booking_time، و
            // CancelUnpaidBookings با status = pending_payment) بدون این ایندکس کل جدول را می‌خواندند.
            $table->index(['status', 'booking_time'], 'bookings_status_booking_time_index');
            $table->index(['customer_course_id', 'status']);
            // سقف روزانه/فعال مشتری (بسته‌ی 5ه) و شمارش «نیامد»ها (NoShowRecord)
            $table->index(['user_id', 'booking_time'], 'bookings_user_time_index');
            $table->index(['user_id', 'status', 'no_show_at'], 'bookings_user_no_show_index');
        });

        // ⭐ Fix (fix/admin-booking-slot-conflict): the check-then-insert pattern used by both
        // the online and manual booking flows is NOT atomic — two concurrent requests for the
        // same specialist/time can both pass the availability check before either commits its
        // INSERT, producing two active bookings for the same slot. Production runs MySQL, which
        // has no native partial/filtered unique index, so a generated STORED column is used
        // instead: `active_slot_key` is NULL for any cancelled booking and a combination of
        // specialist_id+booking_time for anything else. A unique index treats multiple NULLs as
        // non-duplicate (true on MySQL, Postgres, AND SQLite), so cancelled bookings never block
        // re-use of a slot — only two *active* bookings on the exact same specialist_id+
        // booking_time collide, and the second INSERT fails at the database level with a
        // duplicate-key error, which BookingService catches and re-throws as the same
        // user-facing BookingNotAvailableException the availability pre-check already throws.
        //
        // ⚠️ This project's test suite runs on SQLite (in-memory) — a different SQL dialect than
        // production MySQL — so this branches by driver rather than assuming MySQL syntax
        // everywhere. Postgres has genuine native partial unique indexes and needs no generated
        // column at all.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX bookings_active_slot_unique ON bookings (specialist_id, booking_time) WHERE status <> 'cancelled'"
            );

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement(
                'ALTER TABLE bookings ADD COLUMN active_slot_key TEXT '.
                "GENERATED ALWAYS AS (CASE WHEN status <> 'cancelled' THEN specialist_id || '_' || booking_time ELSE NULL END) STORED"
            );
        } else {
            // mysql (production) and any other MySQL-compatible driver.
            DB::statement(
                'ALTER TABLE bookings ADD COLUMN active_slot_key VARCHAR(191) '.
                "GENERATED ALWAYS AS (CASE WHEN status <> 'cancelled' THEN CONCAT(specialist_id, '_', booking_time) ELSE NULL END) STORED ".
                'AFTER updated_at'
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('active_slot_key', 'bookings_active_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('customer_courses');
        Schema::dropIfExists('course_settings');
        Schema::dropIfExists('course_packages');
    }
};
