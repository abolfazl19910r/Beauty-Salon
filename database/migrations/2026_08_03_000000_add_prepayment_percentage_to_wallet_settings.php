<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Added by explicit user decision: the prepayment amount used to be hardcoded in
 * BookingService (30% of the service price, minimum 50,000 toman) — not configurable by the
 * admin at all. Business case for keeping it percentage-based rather than a flat amount: a flat
 * prepayment on an expensive service is both a weak commitment from the customer and, more
 * importantly, makes the cancellation-fee system (which is itself a percentage of the prepayment)
 * nearly meaningless for expensive bookings — the maximum possible cancellation fee would stay
 * capped at the same small flat amount regardless of how much specialist time was reserved.
 *
 * Defaults below (30%, 50,000) exactly match the previous hardcoded behavior, so applying this
 * migration alone changes nothing until the admin explicitly adjusts these on
 * admin/wallet/settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->decimal('prepayment_percentage', 5, 2)
                ->default(30)
                ->after('admin_commission_percentage');

            $table->decimal('minimum_prepayment_amount', 12, 2)
                ->default(50000)
                ->after('prepayment_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->dropColumn(['prepayment_percentage', 'minimum_prepayment_amount']);
        });
    }
};
