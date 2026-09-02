<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Customer identity redesign (confirmed 2026-08-30 — see "🔴 بازطراحی هویت مشتری" in
 * Rasta_unified_prompt.md): only customer accounts become per-salon; admin/specialist accounts
 * keep today's globally-unique phone behavior untouched, since they connect to a salon through
 * salon_admins / specialists.salon_id already, not through this table.
 *
 * `user_type` exists ONLY because a generated column (needed for the split unique constraints
 * below, same MySQL-has-no-partial-unique-index situation as bookings.active_slot_key) can only
 * reference columns on its own row — it cannot join out to `specialists` to check "does this
 * user have a specialist record", which is how "customer" is actually defined everywhere else
 * in this codebase (is_admin=false AND no linked Specialist). This column is a persisted,
 * same-row-only stand-in for that check, kept in sync at the two points a User's nature
 * changes: registration (always 'customer') and admin/specialist creation.
 *
 * ⚠️ Known limitation, not solved by this migration: AdminSpecialistService::create() can link
 * an EXISTING 'customer' user (matched by phone) to a new Specialist record. That user's
 * `user_type` is not flipped to 'staff' here — doing so correctly means also reconciling their
 * salon_id against the specialist's salon_id, which is a separate, real decision (documented as
 * a follow-up in Rasta_unified_prompt.md, not implemented in this migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->nullOnDelete();
            $table->enum('user_type', ['staff', 'customer'])->default('customer')->after('salon_id');
        });

        // Backfill: anyone currently admin OR linked to a Specialist record is 'staff' (global,
        // cross-salon phone uniqueness unchanged for them); everyone else is a 'customer' and
        // gets attached to the one default salon that already exists (migration
        // 2026_08_29_000103_backfill_default_salon_and_salon_id) — matches how every other
        // salon-owned table was backfilled.
        $defaultSalonId = DB::table('salons')->where('slug', 'rasta')->value('id');

        DB::table('users')
            ->where('is_admin', true)
            ->orWhereIn('id', DB::table('specialists')->whereNotNull('user_id')->pluck('user_id'))
            ->update(['user_type' => 'staff']);

        DB::table('users')
            ->where('user_type', 'customer')
            ->update(['salon_id' => $defaultSalonId]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX users_staff_phone_unique ON users (phone) WHERE user_type = 'staff'");
            DB::statement("CREATE UNIQUE INDEX users_customer_salon_phone_unique ON users (salon_id, phone) WHERE user_type = 'customer'");

            return;
        }

        $concat = $driver === 'sqlite'
            ? "salon_id || '_' || phone"
            : "CONCAT(salon_id, '_', phone)";

        Schema::table('users', function (Blueprint $table) use ($concat) {
            $table->string('staff_phone_key', 20)
                ->storedAs("CASE WHEN user_type = 'staff' THEN phone ELSE NULL END");
            $table->string('customer_salon_phone_key', 191)
                ->storedAs("CASE WHEN user_type = 'customer' THEN {$concat} ELSE NULL END");
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('staff_phone_key', 'users_staff_phone_unique');
            $table->unique('customer_salon_phone_key', 'users_customer_salon_phone_unique');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_staff_phone_unique');
            DB::statement('DROP INDEX IF EXISTS users_customer_salon_phone_unique');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_staff_phone_unique');
                $table->dropUnique('users_customer_salon_phone_unique');
                $table->dropColumn(['staff_phone_key', 'customer_salon_phone_key']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone', 'users_phone_unique');
            $table->dropColumn('user_type');
            $table->dropConstrainedForeignId('salon_id');
        });
    }
};
