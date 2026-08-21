<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Feature completion (test-writing session 9, per explicit project decision): the
 * "specialist break time" logic in Specialist::getAvailableSlots() has existed since the
 * table was first created, reading $schedule->break_start/break_end to carve a lunch-break-
 * style gap out of the day's available slots. Neither column ever existed on
 * specialist_schedules, so this branch was always inert (`if ($schedule->break_start &&
 * $schedule->break_end)` was always false) — confirmed and documented as a dead-code
 * candidate in an earlier test-writing session. Per the project's decision to complete the
 * schema (rather than delete the now-dead branch), both columns are added here as nullable
 * — a specialist/day with no break behaves exactly as before (no gap carved out).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialist_schedules', function (Blueprint $table) {
            $table->time('break_start')->nullable()->after('end_time');
            $table->time('break_end')->nullable()->after('break_start');
        });
    }

    public function down(): void
    {
        Schema::table('specialist_schedules', function (Blueprint $table) {
            $table->dropColumn(['break_start', 'break_end']);
        });
    }
};
