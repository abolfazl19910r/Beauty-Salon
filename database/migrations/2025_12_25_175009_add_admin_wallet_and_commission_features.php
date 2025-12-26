<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->decimal('admin_commission_percentage', 5, 2)->default(10)->after('withdrawal_fee_percentage');
        });

        Schema::create('admin_wallet', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('admin_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_wallet_id')->constrained('admin_wallet')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->enum('type', ['commission', 'withdrawal', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['admin_wallet_id', 'created_at']);
        });

        DB::table('admin_wallet')->insert([
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_wallet_transactions');
        Schema::dropIfExists('admin_wallet');

        Schema::table('wallet_settings', function (Blueprint $table) {
            $table->dropColumn('admin_commission_percentage');
        });
    }
};
