<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('points');
            $table->enum('type', ['earned', 'spent'])->default('earned');
            $table->string('description');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('required_points');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('required_points');
        });

        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points_required');
            $table->decimal('discount_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('loyalty_settings')->insert([
            [
                'key' => 'points_per_amount',
                'value' => '10000',
                'description' => 'میزان امتیاز به ازای هر 1000 تومان خرید',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'points_expiry_months',
                'value' => '12',
                'description' => 'مدت زمان اعتبار امتیازها (ماه)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'minimum_points_for_discount',
                'value' => '1000',
                'description' => 'حداقل امتیاز لازم برای دریافت تخفیف',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalties');
        Schema::dropIfExists('loyalty_settings');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('loyalty_points');
    }
};
