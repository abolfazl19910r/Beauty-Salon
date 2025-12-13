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
        Schema::create('specialist_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

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

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['specialist_id', 'date']);
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('reason')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['specialist_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('specialist_schedules');
    }
};
