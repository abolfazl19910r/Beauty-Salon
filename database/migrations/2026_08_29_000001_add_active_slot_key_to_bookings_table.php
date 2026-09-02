<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 1): the check-then-insert pattern used by
 * both the online flow (BookingService::createBooking()) and the new manual flow
 * (BookingService::createManualBooking(), added in commit 2 of this branch) is NOT atomic —
 * two concurrent requests for the same specialist/time (e.g. one customer booking online while
 * an admin is on the phone confirming the same slot to a walk-in caller) can both pass the
 * availability check before either one commits its INSERT, producing two active bookings for
 * the same slot.
 *
 * Production runs MySQL (see .env: DB_CONNECTION=mysql), which has no native partial/filtered
 * unique index, so a generated STORED column is used instead: `active_slot_key` is NULL for any
 * cancelled booking and a combination of specialist_id+booking_time for anything else. A
 * unique index treats multiple NULLs as non-duplicate (true on MySQL, Postgres, AND SQLite), so
 * cancelled bookings never block re-use of a slot — only two *active* bookings on the exact
 * same specialist_id+booking_time collide, and the second INSERT fails at the database level
 * with a duplicate-key error. BookingService catches that QueryException and re-throws it as the
 * same user-facing BookingNotAvailableException the availability pre-check already throws, so
 * the customer/admin sees the same friendly Persian message either way — never a raw SQL error.
 *
 * ⚠️ This project's test suite runs on SQLite (phpunit.xml: DB_CONNECTION=sqlite, in-memory) —
 * a different SQL dialect than production MySQL, so this migration branches by driver rather
 * than assuming MySQL syntax everywhere. MySQL's CONCAT() and generated-column syntax differ
 * from SQLite's `||` concatenation operator; both were verified directly (a standalone SQLite
 * check confirmed cancelled duplicates are allowed and a second active booking on the same slot
 * is correctly rejected) before writing this. Postgres has genuine native partial unique
 * indexes and needs no generated column at all — included here since a future move to Postgres
 * was discussed for this project, even though production is MySQL today.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Postgres: a real partial unique index — no extra column needed.
            DB::statement(
                "CREATE UNIQUE INDEX bookings_active_slot_unique ON bookings (specialist_id, booking_time) WHERE status <> 'cancelled'"
            );

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement(
                "ALTER TABLE bookings ADD COLUMN active_slot_key TEXT ".
                "GENERATED ALWAYS AS (CASE WHEN status <> 'cancelled' THEN specialist_id || '_' || booking_time ELSE NULL END) STORED"
            );
        } else {
            // mysql (production) and any other MySQL-compatible driver.
            DB::statement(
                "ALTER TABLE bookings ADD COLUMN active_slot_key VARCHAR(191) ".
                "GENERATED ALWAYS AS (CASE WHEN status <> 'cancelled' THEN CONCAT(specialist_id, '_', booking_time) ELSE NULL END) STORED"
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('active_slot_key', 'bookings_active_slot_unique');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS bookings_active_slot_unique');

            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_active_slot_unique');
            $table->dropColumn('active_slot_key');
        });
    }
};
