<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 1): AdminBookingController::store() has
 * never distinguished how a booking was created — every booking looked identical whether it
 * came from the online customer flow or was entered manually by an admin for a phone/walk-in
 * customer. This column is purely for reporting/audit; it has NO effect on slot-availability
 * logic (Specialist::getAvailableSlots() already blocks on ANY non-cancelled booking for that
 * specialist/time regardless of source — that's the correct, existing behavior and is left
 * untouched here). Backfilled to 'online' for all existing rows since, before this fix shipped,
 * AdminBookingController::store() bypassed the availability check entirely rather than
 * representing a distinct legitimate "manual" path — see the next migration in this commit
 * for the actual conflict-prevention fix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('source', ['online', 'phone', 'walk_in'])
                ->default('online')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
