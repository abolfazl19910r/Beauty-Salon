<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('beauty_services');
            $table->foreignId('specialist_id')->constrained('specialists');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('booking_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'pending_payment', 'completed'])->default('pending');
            // Purely for reporting/audit; has NO effect on slot-availability logic
            // (Specialist::getAvailableSlots() blocks on ANY non-cancelled booking for that
            // specialist/time regardless of source).
            $table->enum('source', ['online', 'phone', 'walk_in'])->default('online');
            $table->string('discount_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('prepayment_amount', 10, 2)->default(50000);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
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

            $table->index('cancelled_by');
            $table->index('cancelled_at');
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
                "GENERATED ALWAYS AS (CASE WHEN status <> 'cancelled' THEN CONCAT(specialist_id, '_', booking_time) ELSE NULL END) STORED"
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('active_slot_key', 'bookings_active_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
