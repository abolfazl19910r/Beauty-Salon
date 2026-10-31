<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->string('name');
            $table->string('sms_name', 20)->nullable(); // نام کوتاه پیامکی؛ خالی = نام اصلی
            $table->string('slug');
            // ⭐ یکتا در هر سالن، نه در کل جدول (۲۰۲۶-۱۰-۱۱): سالن دوم باید بتواند خدمت هم‌نام سالن دیگر بسازد.
            $table->unique(['salon_id', 'slug'], 'beauty_services_salon_slug_unique');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration')->comment('مدت زمان به دقیقه');
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_services');
    }
};
