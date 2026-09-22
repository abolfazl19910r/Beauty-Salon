<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->decimal('pending_amount', 15, 2)->default(0);
            $table->string('iban')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->boolean('iban_verified')->default(false);
            $table->timestamps();

            $table->index('specialist_id');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('specialist_wallets')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->enum('type', ['income', 'withdrawal', 'cancellation_fee', 'refund', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('type');
        });

        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('specialist_wallets')->onDelete('cascade');
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->string('reference_code')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->enum('method', ['instant', 'iban'])->default('iban');
            $table->string('iban');
            $table->string('account_holder_name');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('payment_details')->nullable();
            $table->timestamps();

            $table->index(['specialist_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->decimal('withdrawal_fee_percentage', 5, 2)->default(2.5);
            // ⭐ Added by explicit user decision: admin's cut of every booking's prepayment.
            $table->decimal('admin_commission_percentage', 5, 2)->default(10);
            // ⭐ Added by explicit user decision: the prepayment amount used to be hardcoded in
            // BookingService (30% of the service price, minimum 50,000 toman) — not configurable
            // by the admin at all. Business case for keeping it percentage-based rather than a
            // flat amount: a flat prepayment on an expensive service is both a weak commitment
            // from the customer and, more importantly, makes the cancellation-fee system (itself
            // a percentage of the prepayment) nearly meaningless for expensive bookings — the
            // maximum possible cancellation fee would stay capped at the same small flat amount
            // regardless of how much specialist time was reserved. Defaults below (30%, 50,000)
            // exactly match the previous hardcoded behavior.
            $table->decimal('prepayment_percentage', 5, 2)->default(30);
            $table->decimal('minimum_prepayment_amount', 12, 2)->default(50000);
            $table->decimal('minimum_withdrawal_amount', 15, 2)->default(100000);
            $table->decimal('maximum_withdrawal_amount', 15, 2)->default(50000000);
            $table->boolean('instant_withdrawal_enabled')->default(false);
            $table->decimal('instant_withdrawal_fee', 15, 2)->default(5000);
            $table->integer('cancellation_before_hours')->default(24);
            $table->decimal('customer_cancellation_fee_percentage', 5, 2)->default(20);
            $table->decimal('specialist_cancellation_penalty_percentage', 5, 2)->default(10);
            // ⭐ Added by explicit user request (suggestions 1 and 4 on the cancellation logic):
            // 1) `specialist_cancellation_before_hours` — previously the specialist cancellation
            // penalty had no time threshold (unlike the client, which has
            // `cancellation_before_hours`) — meaning even a cancellation a month before the
            // appointment was subject to a penalty. Kept as its own column, not shared with the
            // client column, since the reasonable interval for a client and a specialist isn't
            // necessarily the same.
            $table->integer('specialist_cancellation_before_hours')->default(24);
            // 2) `specialist_repeat_cancellation_*` — the aggravated penalty for repeated
            // cancellations: if the specialist cancels `_threshold` or more appointments within
            // `_window_days`, the penalty percentage for THAT cancellation (not previous ones)
            // increases by `_extra_percentage`. `_threshold = 0` disables this entirely (default,
            // so existing behavior never changes without a deliberate admin adjustment).
            $table->unsignedInteger('specialist_repeat_cancellation_threshold')->default(0);
            $table->unsignedInteger('specialist_repeat_cancellation_window_days')->default(30);
            $table->decimal('specialist_repeat_cancellation_extra_percentage', 5, 2)->default(0);
            $table->integer('settlement_delay_days')->default(2);
            $table->timestamps();
        });

        // See 0000_01_01_000000_create_salons_table.php's own comment — RefreshDatabase runs
        // migrations only, never seeders, so the baseline row every salon-owned table needs has
        // to be created here, tied to the one default salon that migration guarantees exists.
        $salonId = DB::table('salons')->where('slug', 'rasta')->value('id');

        DB::table('wallet_settings')->insert([
            'salon_id' => $salonId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('specialist_wallets');
        Schema::dropIfExists('wallet_settings');
    }
};
