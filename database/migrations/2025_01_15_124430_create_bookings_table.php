<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('beauty_services');
            $table->foreignId('specialist_id')->constrained('specialists');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('booking_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled']);
            $table->string('discount_code')->nullable();
            $table->decimal('prepayment_amount', 10, 2)->default(50000);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->enum('refund_status', ['pending', 'refunded', 'failed'])->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->string('refund_reference')->nullable();
            $table->json('refund_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
