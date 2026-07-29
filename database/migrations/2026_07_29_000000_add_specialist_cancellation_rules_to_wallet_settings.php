<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Added by explicit user request (suggestions 1 and 4 on the cancellation logic):
 *
 * 1) `specialist_cancellation_before_hours` — Previously, the specialist cancellation penalty had no time
 * threshold (unlike the client, which had `cancellation_before_hours`) — meaning that even
 * cancellations a month before the appointment were subject to a penalty. This separate column (not shared with the client column,
 * since the reasonable interval for the client and the specialist is not necessarily the same) fixes this asymmetry.
 *
 * 2) `specialist_repeat_cancellation_*` — three columns for the aggravated penalty for repeated cancellations:
 * If the specialist cancels a certain number
 * (`_threshold`) or more appointments in a time window (`_window_days`), the penalty percentage for that cancellation (not previous cancellations)
 * increases by `_extra_percentage`. `_threshold = 0` means this
 * feature is completely disabled (default, to not change existing behavior without manual admin adjustment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->integer('specialist_cancellation_before_hours')
                ->default(24)
                ->after('specialist_cancellation_penalty_percentage');

            $table->unsignedInteger('specialist_repeat_cancellation_threshold')
                ->default(0)
                ->after('specialist_cancellation_before_hours');

            $table->unsignedInteger('specialist_repeat_cancellation_window_days')
                ->default(30)
                ->after('specialist_repeat_cancellation_threshold');

            $table->decimal('specialist_repeat_cancellation_extra_percentage', 5, 2)
                ->default(0)
                ->after('specialist_repeat_cancellation_window_days');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->dropColumn([
                'specialist_cancellation_before_hours',
                'specialist_repeat_cancellation_threshold',
                'specialist_repeat_cancellation_window_days',
                'specialist_repeat_cancellation_extra_percentage',
            ]);
        });
    }
};
