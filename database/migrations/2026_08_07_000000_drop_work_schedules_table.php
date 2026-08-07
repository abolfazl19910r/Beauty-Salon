<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the `work_schedules` table.
 *
 * WorkSchedule was a fully-implemented, bug-free feature (one shared time range applied to a
 * set of selected days per specialist, enforced via a `unique('specialist_id')` constraint —
 * i.e. exactly one row per specialist) built alongside the pre-existing, already-in-production
 * `specialist_schedules` table (which stores a separate start/end time per day of week and is
 * the ONLY thing `Specialist::getAvailableSlots()` — the method that actually drives the
 * customer booking flow — ever reads from).
 *
 * WorkSchedule never had a real consumer: it duplicated a subset of what SpecialistSchedule
 * already did, at the cost of maintaining two parallel scheduling systems. After confirming
 * (again, deliberately, before this migration) that no code path anywhere reads from
 * `work_schedules`, the decision was made to remove the feature entirely rather than either
 * finish wiring it in or leave it as unused dead code indefinitely.
 *
 * `down()` only recreates the schema, not any data that existed in the table at drop time —
 * this is a one-way cleanup of a table that was never wired into any real user-facing feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_schedules');
    }

    public function down(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->json('work_days');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('specialist_id');
        });
    }
};
