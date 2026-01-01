<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('beauty_services')->onDelete('cascade');

            $table->tinyInteger('overall_rating')->unsigned();
            $table->tinyInteger('quality_rating')->unsigned();
            $table->tinyInteger('behavior_rating')->unsigned();
            $table->tinyInteger('cleanliness_rating')->unsigned();
            $table->tinyInteger('speed_rating')->unsigned();

            $table->text('comment')->nullable();
            $table->string('review_token', 64)->unique();
            $table->timestamp('reviewed_at')->nullable();

            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->text('specialist_response')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['specialist_id', 'created_at']);
            $table->index(['overall_rating', 'created_at']);
            $table->index('is_approved');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('review_sent_at')->nullable()->after('review');
            $table->timestamp('reviewed_at')->nullable()->after('review_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['review_sent_at', 'reviewed_at']);
        });

        Schema::dropIfExists('reviews');
    }
};
