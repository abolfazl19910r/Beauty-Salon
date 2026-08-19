<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Fix (test-writing session 8): StoreAdminBookingRequest/UpdateAdminBookingRequest both
 * validate a `notes` field, and admin/bookings/{create,edit,show}.blade.php all render a
 * `notes` textarea/display — but the `bookings` table never had a `notes` column in any
 * migration, and `notes` was not in Booking::$fillable either. Every admin-entered note was
 * silently discarded on save (Booking::create()/update() just dropped the unknown key,
 * since it wasn't fillable — no error was ever thrown). This is a variant of the project's
 * recurring "form accepts a value, it's silently thrown away" pattern (see
 * admin_commission_percentage, BlogCategory::description/order, Leave::approved_at, etc. in
 * Rasta_unified_prompt.md) — except here the column itself never existed at all, not just
 * the $fillable entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('review');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
