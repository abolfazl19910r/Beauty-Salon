<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });

        Schema::create('beauty_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration')->comment('مدت زمان به دقیقه');
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('specialists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('specialist_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained()->onDelete('cascade');
            $table->foreignId('beauty_service_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['specialist_id', 'beauty_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_services');
        Schema::dropIfExists('specialists');
        Schema::dropIfExists('beauty_services');
        Schema::dropIfExists('categories');
    }
};
