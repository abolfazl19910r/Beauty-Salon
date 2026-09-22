<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->unique();
            $table->boolean('auto_confirm_bookings')->default(false);
            $table->decimal('commission_rate', 5, 2)
                ->nullable()
                ->default(null)
                ->comment('نرخ کمیسیون اختصاصی (%). null = استفاده از تنظیمات global');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialists');
    }
};
