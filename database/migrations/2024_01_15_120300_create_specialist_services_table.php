<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('specialist_id');
            $table->unsignedBigInteger('beauty_service_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_services');
    }
};
