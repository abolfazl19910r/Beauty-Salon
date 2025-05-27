<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
