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
            $table->decimal('withdrawal_fee_percentage', 5, 2)->default(2.5);
            $table->decimal('minimum_withdrawal_amount', 15, 2)->default(100000);
            $table->decimal('maximum_withdrawal_amount', 15, 2)->default(50000000);
            $table->boolean('instant_withdrawal_enabled')->default(false);
            $table->decimal('instant_withdrawal_fee', 15, 2)->default(5000);
            $table->integer('cancellation_before_hours')->default(24);
            $table->decimal('customer_cancellation_fee_percentage', 5, 2)->default(20);
            $table->decimal('specialist_cancellation_penalty_percentage', 5, 2)->default(10);
            $table->integer('settlement_delay_days')->default(2);
            $table->timestamps();
        });

        // Insert default settings
        DB::table('wallet_settings')->insert([
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
