<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        DB::table('loyalty_settings')->insert([
            [
                'key' => 'points_per_amount',
                'value' => '10000', // 1 امتیاز به ازای هر 10000 تومان
                'description' => 'میزان امتیاز به ازای هر 1000 تومان خرید'
            ],
            [
                'key' => 'points_expiry_months',
                'value' => '12', // امتیازها بعد از 12 ماه منقضی می‌شوند
                'description' => 'مدت زمان اعتبار امتیازها (ماه)'
            ],
            [
                'key' => 'minimum_points_for_discount',
                'value' => '1000', // حداقل امتیاز برای تبدیل به تخفیف
                'description' => 'حداقل امتیاز لازم برای دریافت تخفیف'
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
